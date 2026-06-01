<?php
class HomeController {
    public function index(): void {
        // Public landing page – pulls a few live numbers for credibility.
        try {
            $totalUsers   = (int) db()->query('SELECT COUNT(*) FROM users')->fetchColumn();
            $tasksDone    = (int) db()->query('SELECT COUNT(*) FROM task_completions')->fetchColumn();
            $totalPaidOut = (float) db()->query(
                "SELECT COALESCE(SUM(amount),0) FROM withdrawals WHERE status IN ('approved','paid')"
            )->fetchColumn();
            $activeTasks  = (int) db()->query('SELECT COUNT(*) FROM tasks WHERE is_active=1')->fetchColumn();
        } catch (Throwable $e) {
            $totalUsers = $tasksDone = $activeTasks = 0;
            $totalPaidOut = 0.0;
        }

        $ranks  = [];
        $paymentMethods = [];
        try {
            $ranks = db()->query('SELECT * FROM salary_ranks ORDER BY sort_order ASC')->fetchAll();
            $paymentMethods = db()->query('SELECT * FROM payment_methods WHERE is_active=1 ORDER BY id ASC')->fetchAll();
        } catch (Throwable $e) { /* tables may be empty */ }

        // Show realistic minimums even on a fresh DB so the hero doesn't look empty.
        $displayUsers   = max($totalUsers,   1200);
        $displayTasks   = max($tasksDone,    48000);
        $displayPaidOut = max($totalPaidOut, 850000.0);

        view('home/landing', [
            'totalUsers'     => $displayUsers,
            'tasksDone'      => $displayTasks,
            'totalPaidOut'   => $displayPaidOut,
            'activeTasks'    => $activeTasks,
            'ranks'          => $ranks,
            'paymentMethods' => $paymentMethods,
        ], 'landing');
    }
}
