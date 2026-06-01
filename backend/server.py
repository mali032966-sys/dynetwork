"""
DYNOVA reverse proxy.

Supervisor pins this process to 0.0.0.0:8001 and the Emergent ingress routes
every `/api/*` request here. We forward every request to the PHP built-in
server running on 127.0.0.1:9000 which serves /app/dynova/public via
router.php. router.php expects the `/api` prefix (DYNOVA_BASE_URL=/api) and
strips it before dispatching, so no rewriting is needed on our side.
"""

import os
import asyncio
import logging
import subprocess
from contextlib import asynccontextmanager
from pathlib import Path

import httpx
from fastapi import FastAPI, Request, Response
from fastapi.responses import PlainTextResponse

PHP_HOST = "127.0.0.1"
PHP_PORT = 9000
PHP_DOCROOT = "/app/dynova/public"
PHP_ROUTER = "/app/dynova/public/router.php"

# Hop-by-hop headers that MUST NOT be forwarded across a proxy boundary
HOP_BY_HOP = {
    "connection", "keep-alive", "proxy-authenticate", "proxy-authorization",
    "te", "trailers", "transfer-encoding", "upgrade", "content-encoding",
    "content-length",
}

logging.basicConfig(level=logging.INFO, format="%(asctime)s %(levelname)s %(message)s")
log = logging.getLogger("dynova-proxy")

php_proc: subprocess.Popen | None = None


def run_bootstrap() -> None:
    """Self-heal: install PHP/MariaDB if missing, start MariaDB, ensure schema."""
    script = "/app/scripts/dynova_bootstrap.sh"
    try:
        log.info("Running %s", script)
        r = subprocess.run(
            ["bash", script], check=False, capture_output=True, text=True, timeout=180,
        )
        log.info("bootstrap rc=%s", r.returncode)
        if r.stdout:
            log.info("bootstrap stdout: %s", r.stdout.strip()[-500:])
        if r.stderr:
            log.info("bootstrap stderr: %s", r.stderr.strip()[-500:])
    except Exception as e:
        log.exception("bootstrap failed: %s", e)


def start_php_server() -> subprocess.Popen:
    """Spawn the PHP built-in server in the background."""
    env = os.environ.copy()
    env["DYNOVA_BASE_URL"] = "/api"
    env["DYNOVA_DB_HOST"] = "127.0.0.1"
    env["DYNOVA_DB_PORT"] = "3306"
    env["DYNOVA_DB_NAME"] = "dynova_network"
    env["DYNOVA_DB_USER"] = "dynova"
    env["DYNOVA_DB_PASS"] = "dynova_pass_2026"

    log_path = Path("/var/log/dynova-php.log")
    log_fp = open(log_path, "ab", buffering=0)

    cmd = [
        "php", "-d", "display_errors=1", "-d", "log_errors=1",
        "-S", f"{PHP_HOST}:{PHP_PORT}", "-t", PHP_DOCROOT, PHP_ROUTER,
    ]
    log.info("Starting PHP: %s", " ".join(cmd))
    proc = subprocess.Popen(
        cmd, env=env, cwd=PHP_DOCROOT,
        stdout=log_fp, stderr=log_fp, start_new_session=True,
    )
    return proc


async def wait_for_php(timeout: float = 15.0) -> None:
    """Poll until PHP responds (or timeout)."""
    deadline = asyncio.get_event_loop().time() + timeout
    async with httpx.AsyncClient(timeout=2.0) as c:
        while asyncio.get_event_loop().time() < deadline:
            try:
                r = await c.get(f"http://{PHP_HOST}:{PHP_PORT}/api/")
                if r.status_code < 500:
                    log.info("PHP server ready (status=%s)", r.status_code)
                    return
            except Exception:
                pass
            await asyncio.sleep(0.3)
    log.warning("PHP server did not respond within %ss", timeout)


@asynccontextmanager
async def lifespan(app: FastAPI):
    global php_proc
    run_bootstrap()
    php_proc = start_php_server()
    await wait_for_php()
    yield
    if php_proc and php_proc.poll() is None:
        php_proc.terminate()
        try:
            php_proc.wait(timeout=5)
        except subprocess.TimeoutExpired:
            php_proc.kill()


app = FastAPI(lifespan=lifespan)

# A single shared client – keep-alive to PHP server.
# `cookies=None` is the default but we explicitly disable the per-client cookie
# jar so sessions are never shared between different end-users.
http_client: httpx.AsyncClient = httpx.AsyncClient(
    base_url=f"http://{PHP_HOST}:{PHP_PORT}",
    timeout=httpx.Timeout(60.0, connect=5.0),
    follow_redirects=False,
    cookies=None,
)


@app.get("/healthz")
async def healthz():
    return {"ok": True, "php_pid": php_proc.pid if php_proc else None}


async def _proxy(request: Request, path: str) -> Response:
    # Re-attach `/api` prefix because router.php expects it.
    target_path = "/api" + (("/" + path) if path else "/")
    query = request.url.query
    url = target_path + (f"?{query}" if query else "")

    fwd_headers = {}
    for k, v in request.headers.items():
        lk = k.lower()
        if lk in HOP_BY_HOP or lk == "host":
            continue
        fwd_headers[k] = v
    # Preserve original host for the PHP app (cookies / absolute URLs).
    host = request.headers.get("host")
    if host:
        fwd_headers["X-Forwarded-Host"] = host
        fwd_headers["X-Forwarded-Proto"] = request.url.scheme
    client_ip = request.client.host if request.client else ""
    if client_ip:
        fwd_headers["X-Forwarded-For"] = client_ip

    body = await request.body()

    try:
        # IMPORTANT: httpx.AsyncClient keeps a process-wide cookie jar even when
        # constructed with cookies=None. Without clearing it, the first user
        # to log in would have their session cookie attached to every other
        # user's outgoing request. Wipe before every request to guarantee
        # isolation – the Cookie header from the real client is still forwarded
        # via `fwd_headers`.
        http_client.cookies.clear()
        upstream = await http_client.request(
            request.method, url, headers=fwd_headers, content=body,
        )
    except httpx.RequestError as e:
        log.exception("Upstream PHP error: %s", e)
        return PlainTextResponse(
            f"Upstream PHP error: {e}", status_code=502,
        )

    # Build raw header list so duplicate headers (e.g. multiple Set-Cookie)
    # are preserved end-to-end. httpx exposes them as a list of byte tuples
    # via `headers.raw`. Filter hop-by-hop headers because the proxy already
    # decoded content-encoding / re-frames content-length.
    raw_headers: list[tuple[bytes, bytes]] = []
    for name_b, value_b in upstream.headers.raw:
        if name_b.decode("latin-1").lower() in HOP_BY_HOP:
            continue
        raw_headers.append((name_b, value_b))

    response = Response(
        content=upstream.content,
        status_code=upstream.status_code,
    )
    # Replace the auto-generated headers with the upstream set + the
    # content-length that Starlette already computed for our body.
    cl = next(
        ((k, v) for k, v in response.raw_headers if k.lower() == b"content-length"),
        None,
    )
    if cl:
        raw_headers.append(cl)
    response.raw_headers = raw_headers
    return response


@app.api_route(
    "/api",
    methods=["GET", "POST", "PUT", "PATCH", "DELETE", "OPTIONS", "HEAD"],
)
async def proxy_root(request: Request):
    return await _proxy(request, "")


@app.api_route(
    "/api/{path:path}",
    methods=["GET", "POST", "PUT", "PATCH", "DELETE", "OPTIONS", "HEAD"],
)
async def proxy_any(request: Request, path: str):
    return await _proxy(request, path)
