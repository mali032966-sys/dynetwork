<?php
class BonusController {
    public function index(): void {
        $u = require_user();
        $bonuses = JoiningBonus::active();
        view('user/bonuses', compact('u', 'bonuses'), 'app');
    }
}
