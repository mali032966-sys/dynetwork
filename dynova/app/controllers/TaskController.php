<?php
class TaskController {
    public function index(): void {
        $u = require_user();
        $uid = (int)$u['id'];

        // GATE: a user must have an active package to earn from tasks. If they
        // don't, bounce them to the packages page with a clear message.
        $active = TaskPackage::activeForUser($uid);
        if (!$active) {
            flash_set('error', 'Activate a package to start earning from tasks.');
            redirect('packages');
        }

        // Reward per task is driven entirely by the user's active package —
        // no longer stored on the task row.
        $rewardPerTask = TaskPackage::getRewardPerTask($uid);

        $limit = TaskPackage::dailyLimitFor($uid);
        $done  = Task::completedTodayCount($uid);
        $remaining = max(0, $limit - $done);
        $next = ($remaining > 0) ? Task::nextForUser($uid) : null;
        $upcoming = [];
        if ($next) {
            $s = db()->prepare(
                "SELECT * FROM tasks WHERE is_active=1 AND id<>?
                   AND id NOT IN (SELECT task_id FROM task_completions WHERE user_id=? AND DATE(created_at)=CURDATE())
                 ORDER BY id ASC LIMIT 4"
            );
            $s->execute([$next['id'], $uid]);
            $upcoming = $s->fetchAll();
        }
        view(
            'user/tasks',
            compact('u','next','upcoming','remaining','limit','done','rewardPerTask'),
            'app'
        );
    }

    public function submit(): void {
        $u = require_user();
        $uid = (int)$u['id'];

        // GATE: no active package → can't submit tasks.
        if (!TaskPackage::activeForUser($uid)) {
            flash_set('error', 'Activate a package first to start earning.');
            redirect('packages');
        }

        $taskId = (int)($_POST['task_id'] ?? 0);
        $rating = max(1, min(5, (int)($_POST['rating'] ?? 0)));
        if (!$taskId || !$rating) {
            flash_set('error', 'Please select a rating before submitting.');
            redirect('tasks');
        }
        // Daily limit check (driven by the user's active package)
        $limit = TaskPackage::dailyLimitFor($uid);
        if (Task::completedTodayCount($uid) >= $limit) {
            flash_set('error', 'Daily task limit reached.');
            redirect('tasks');
        }
        $task = Task::find($taskId);
        if (!$task || !$task['is_active']) {
            flash_set('error', 'Task not available.');
            redirect('tasks');
        }
        // Already completed today?
        $chk = db()->prepare('SELECT 1 FROM task_completions WHERE user_id=? AND task_id=? AND DATE(created_at)=CURDATE()');
        $chk->execute([$uid, $taskId]);
        if ($chk->fetchColumn()) {
            flash_set('error', 'You already completed this task today.');
            redirect('tasks');
        }

        // Reward comes ONLY from the user's active package now.
        $reward = TaskPackage::getRewardPerTask($uid);
        if ($reward <= 0) {
            flash_set('error', 'Activate a package to start earning.');
            redirect('packages');
        }

        $compId = Task::recordCompletion($uid, $taskId, $rating, $reward);
        User::addBalance($uid, $reward, 'task_earnings');
        Transaction::log($uid, 'task', $reward, $task['title']);

        // Multi-level referral bonuses — only credit ancestors who themselves
        // have an active package. No package = no team earnings.
        $percents = [
            1 => (float)setting('referral_l1', DEFAULT_REFERRAL_L1),
            2 => (float)setting('referral_l2', DEFAULT_REFERRAL_L2),
            3 => (float)setting('referral_l3', DEFAULT_REFERRAL_L3),
        ];
        $chain = User::ancestorChain($uid);
        foreach ($chain as $i => $ancestorId) {
            $level = $i + 1;
            if (!$ancestorId) continue;
            // Gate: ancestor must have an active package to earn referral commissions.
            if (!TaskPackage::activeForUser((int)$ancestorId)) continue;
            $bonus = round($reward * $percents[$level] / 100, 2);
            if ($bonus <= 0) continue;
            User::addBalance((int)$ancestorId, $bonus, 'referral_earnings');
            Referral::log((int)$ancestorId, $uid, $level, $compId, $bonus, $percents[$level]);
            Transaction::log((int)$ancestorId, 'referral', $bonus, "L{$level} from " . ($u['name'] ?: $u['whatsapp']));
        }

        flash_set('success', 'Task submitted! +' . money($reward) . ' added to your wallet.');
        redirect('tasks');
    }
}
