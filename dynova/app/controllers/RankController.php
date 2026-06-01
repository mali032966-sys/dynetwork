<?php
class RankController {
    public function index(): void {
        $u = require_user();
        $ranks = Salary::ranks();

        // Per-level snapshot for the current user
        $stats = [
            'l1_members'  => User::countReferrals((int)$u['id'], 1),
            'l2_members'  => User::countReferrals((int)$u['id'], 2),
            'l3_members'  => User::countReferrals((int)$u['id'], 3),
            'l1_business' => User::teamBusinessAtLevel((int)$u['id'], 1),
            'l2_business' => User::teamBusinessAtLevel((int)$u['id'], 2),
            'l3_business' => User::teamBusinessAtLevel((int)$u['id'], 3),
        ];
        $currentRank = Salary::rankFor((int)$u['id']);

        view('user/ranks', compact('u', 'ranks', 'stats', 'currentRank'), 'app');
    }
}
