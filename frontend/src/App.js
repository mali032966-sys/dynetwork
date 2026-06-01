import { useEffect } from "react";

function App() {
  useEffect(() => {
    // Hand off the entire UI to the PHP app, which is served behind /api/*
    // by the FastAPI reverse proxy (see /app/backend/server.py).
    window.location.replace("/api/");
  }, []);

  return (
    <div
      data-testid="dynova-redirect"
      style={{
        minHeight: "100vh",
        display: "flex",
        alignItems: "center",
        justifyContent: "center",
        background: "#0b1020",
        color: "#cfd6ff",
        fontFamily: "system-ui, sans-serif",
        fontSize: 16,
      }}
    >
      Loading DYNOVA…
    </div>
  );
}

export default App;
