<?php
$currency = APP_CURRENCY_SYMBOL;
?>
<!-- ============================== HERO ============================== -->
<section class="lp-hero">
  <div class="lp-container hero-inner">
    <div class="hero-left reveal">
      <span class="hero-pill" data-testid="hero-pill">
        <i class="fa-solid fa-bolt"></i> Live now · Payouts in PKR
      </span>
      <h1 class="hero-title">
        Rate videos.<br>
        <span class="grad-text">Earn real money.</span><br>
        Build a network that pays.
      </h1>
      <p class="hero-sub">
        DYNOVA NETWORK turns five minutes of your day into a serious income stream.
        Rate sponsored videos, invite friends across three referral levels, climb
        salary ranks and withdraw to JazzCash or EasyPaisa — directly to your phone.
      </p>
      <div class="hero-cta">
        <a href="<?= route_url('auth/signup') ?>" class="btn inline lg" data-testid="hero-signup">
          <i class="fa-solid fa-rocket"></i> Start earning free
        </a>
        <a href="#how" class="btn ghost inline lg" data-testid="hero-how">
          <i class="fa-solid fa-circle-play"></i> See how it works
        </a>
      </div>
      <div class="hero-trust">
        <div class="trust-avs">
          <span class="av" style="background:linear-gradient(135deg,#3eb6ff,#8d5bff)">A</span>
          <span class="av" style="background:linear-gradient(135deg,#ff5be0,#8d5bff)">S</span>
          <span class="av" style="background:linear-gradient(135deg,#3ddc97,#3eb6ff)">M</span>
          <span class="av" style="background:linear-gradient(135deg,#ffb547,#ff5be0)">+</span>
        </div>
        <div>
          <b><span class="counter" data-target="<?= (int)$totalUsers ?>">0</span>+</b> active members trust DYNOVA today
        </div>
      </div>
    </div>

    <div class="hero-right reveal">
      <!-- floating phone mock -->
      <div class="phone-wrap">
        <div class="phone-glow"></div>
        <div class="phone">
          <div class="phone-notch"></div>
          <div class="phone-screen">
            <div class="ps-top">
              <div>
                <small>Welcome back</small>
                <b>Sarah K.</b>
              </div>
              <div class="ps-bell"><i class="fa-regular fa-bell"></i></div>
            </div>
            <div class="ps-balance">
              <div class="ps-balance-lbl">CURRENT BALANCE</div>
              <div class="ps-balance-amt"><?= $currency ?> 12,450<span>.00</span></div>
              <div class="ps-badge"><i class="fa-solid fa-arrow-trend-up"></i> +Rs 350 today</div>
            </div>
            <div class="ps-stats">
              <div class="ps-stat">
                <div class="ico" style="background:rgba(62,182,255,.12);color:#3eb6ff"><i class="fa-solid fa-star"></i></div>
                <small>Tasks</small><b>14<i>/25</i></b>
              </div>
              <div class="ps-stat">
                <div class="ico" style="background:rgba(141,91,255,.12);color:#8d5bff"><i class="fa-solid fa-users"></i></div>
                <small>Referrals</small><b>23</b>
              </div>
              <div class="ps-stat">
                <div class="ico" style="background:rgba(255,181,71,.12);color:#ffb547"><i class="fa-solid fa-trophy"></i></div>
                <small>Rank</small><b>Silver</b>
              </div>
            </div>
            <div class="ps-task">
              <div class="ps-task-thumb">
                <div class="play"><i class="fa-solid fa-play"></i></div>
              </div>
              <div class="ps-task-body">
                <b>Rate this video</b>
                <small>+Rs 75 reward</small>
                <div class="ps-stars">
                  <i class="fa-solid fa-star"></i>
                  <i class="fa-solid fa-star"></i>
                  <i class="fa-solid fa-star"></i>
                  <i class="fa-solid fa-star"></i>
                  <i class="fa-regular fa-star"></i>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- floating chips -->
        <div class="float-chip chip-1">
          <div class="ic"><i class="fa-solid fa-circle-dollar-to-slot"></i></div>
          <div><small>Payout</small><b>+<?= $currency ?> 850</b></div>
        </div>
        <div class="float-chip chip-2">
          <div class="ic violet"><i class="fa-solid fa-user-plus"></i></div>
          <div><small>New referral</small><b>Ali joined</b></div>
        </div>
        <div class="float-chip chip-3">
          <div class="ic green"><i class="fa-solid fa-bolt-lightning"></i></div>
          <div><small>Daily streak</small><b>7 days</b></div>
        </div>
      </div>
    </div>
  </div>

  <!-- live stat strip -->
  <div class="lp-container">
    <div class="stat-strip reveal">
      <div class="ss-item">
        <div class="ss-ico"><i class="fa-solid fa-users"></i></div>
        <div>
          <b><span class="counter" data-target="<?= (int)$totalUsers ?>">0</span>+</b>
          <small>Members</small>
        </div>
      </div>
      <div class="ss-item">
        <div class="ss-ico violet"><i class="fa-solid fa-circle-check"></i></div>
        <div>
          <b><span class="counter" data-target="<?= (int)$tasksDone ?>">0</span>+</b>
          <small>Tasks rated</small>
        </div>
      </div>
      <div class="ss-item">
        <div class="ss-ico green"><i class="fa-solid fa-money-bill-wave"></i></div>
        <div>
          <b><?= $currency ?> <span class="counter" data-target="<?= (int)$totalPaidOut ?>" data-format="short">0</span>+</b>
          <small>Paid out</small>
        </div>
      </div>
      <div class="ss-item">
        <div class="ss-ico amber"><i class="fa-solid fa-shield-halved"></i></div>
        <div>
          <b>99.4%</b>
          <small>On-time payouts</small>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ============================== HOW IT WORKS ============================== -->
<section id="how" class="lp-section">
  <div class="lp-container">
    <div class="lp-section-head reveal">
      <span class="kicker">How it works</span>
      <h2>Three steps. <span class="grad-text">Real cash.</span></h2>
      <p>No experience, no investment, no risk. Sign up in 60 seconds and start earning today.</p>
    </div>
    <div class="how-grid">
      <div class="how-card reveal" style="--d:0s">
        <div class="how-num">01</div>
        <div class="how-ic"><i class="fa-solid fa-user-plus"></i></div>
        <h3>Create your free account</h3>
        <p>Sign up with your WhatsApp number. Get a unique referral code instantly. No card, no fees.</p>
      </div>
      <div class="how-card reveal" style="--d:.1s">
        <div class="how-num">02</div>
        <div class="how-ic"><i class="fa-solid fa-star-half-stroke"></i></div>
        <h3>Rate videos & complete tasks</h3>
        <p>Watch short sponsored videos, give an honest star rating, and earn up to <?= $currency ?> 80 per task. Up to 25 tasks daily.</p>
      </div>
      <div class="how-card reveal" style="--d:.2s">
        <div class="how-num">03</div>
        <div class="how-ic"><i class="fa-solid fa-money-bill-transfer"></i></div>
        <h3>Withdraw to JazzCash / EasyPesa</h3>
        <p>Cash out anytime from <?= $currency ?> 500. Funds arrive on your mobile wallet — usually within hours.</p>
      </div>
    </div>
  </div>
</section>

<!-- ============================== EARNING STREAMS ============================== -->
<section id="earn" class="lp-section alt">
  <div class="lp-container">
    <div class="lp-section-head reveal">
      <span class="kicker">Four ways to earn</span>
      <h2>Stack <span class="grad-text">multiple income streams</span> — all in one app.</h2>
      <p>DYNOVA isn't just task earnings. Build a team and your income compounds automatically every month.</p>
    </div>
    <div class="earn-grid">
      <div class="earn-card reveal" style="--d:0s">
        <div class="earn-ic blue"><i class="fa-solid fa-star"></i></div>
        <h3>Task earnings</h3>
        <p>Earn <?= $currency ?> 50–80 per video rated. Complete up to 25 tasks per day.</p>
        <div class="earn-tag">Up to <?= $currency ?> 2,000/day</div>
      </div>
      <div class="earn-card reveal" style="--d:.08s">
        <div class="earn-ic violet"><i class="fa-solid fa-network-wired"></i></div>
        <h3>3-level referrals</h3>
        <p>Earn <b>10%</b> from L1 friends, <b>5%</b> from L2 and <b>2.5%</b> from L3 — auto-credited every task.</p>
        <div class="earn-tag">Passive · forever</div>
      </div>
      <div class="earn-card reveal" style="--d:.16s">
        <div class="earn-ic amber"><i class="fa-solid fa-trophy"></i></div>
        <h3>Monthly salary</h3>
        <p>Hit rank milestones and unlock a fixed monthly salary — paid automatically on the 1st of every month.</p>
        <div class="earn-tag">Up to <?= $currency ?> 48,000/month</div>
      </div>
      <div class="earn-card reveal" style="--d:.24s">
        <div class="earn-ic green"><i class="fa-solid fa-gift"></i></div>
        <h3>Bonuses &amp; events</h3>
        <p>Monthly leaderboards, milestone gifts and limited-time tasks with boosted rewards.</p>
        <div class="earn-tag">Surprise rewards</div>
      </div>
    </div>
  </div>
</section>

<!-- ============================== RANKS ============================== -->
<section id="ranks" class="lp-section">
  <div class="lp-container">
    <div class="lp-section-head reveal">
      <span class="kicker">Salary ranks</span>
      <h2>Climb the ladder. <span class="grad-text">Get paid every month.</span></h2>
      <p>The more you refer, the higher your rank — and the higher your monthly salary, on top of everything else you earn.</p>
    </div>
    <div class="ranks-grid">
      <?php
      $rankFallback = [
          ['name'=>'Bronze',  'emoji'=>'🥉','min_referrals'=>5,  'min_business'=>10000,  'monthly_salary'=>2000],
          ['name'=>'Silver',  'emoji'=>'🥈','min_referrals'=>20, 'min_business'=>50000,  'monthly_salary'=>8000],
          ['name'=>'Gold',    'emoji'=>'🥇','min_referrals'=>50, 'min_business'=>200000, 'monthly_salary'=>20000],
          ['name'=>'Diamond', 'emoji'=>'💎','min_referrals'=>100,'min_business'=>500000, 'monthly_salary'=>48000],
      ];
      $displayRanks = !empty($ranks) ? $ranks : $rankFallback;
      $tones = ['bronze','silver','gold','diamond'];
      foreach ($displayRanks as $i => $r):
        $tone = $tones[$i % 4];
      ?>
        <div class="rank-card reveal <?= $tone ?>" style="--d:<?= $i * 0.08 ?>s">
          <div class="rank-emoji"><?= e($r['emoji']) ?></div>
          <h3><?= e($r['name']) ?></h3>
          <div class="rank-salary"><?= $currency ?> <?= number_format((float)$r['weekly_salary']) ?><small>/week</small></div>
          <ul class="rank-reqs">
            <li><i class="fa-solid fa-users"></i> <?= (int)$r['min_referrals'] ?>+ direct referrals</li>
            <li><i class="fa-solid fa-chart-line"></i> <?= $currency ?> <?= number_format((float)$r['min_business']) ?>+ team business</li>
            <li><i class="fa-solid fa-infinity"></i> Plus all task &amp; referral income</li>
          </ul>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ============================== PAYMENT METHODS ============================== -->
<section id="payments" class="lp-section alt">
  <div class="lp-container">
    <div class="lp-section-head reveal">
      <span class="kicker">Instant payouts</span>
      <h2>Withdraw to <span class="grad-text">JazzCash or EasyPaisa</span></h2>
      <p>No bank account required. No paperwork. Cash arrives on your mobile wallet in hours, not days.</p>
    </div>
    <div class="pay-grid">
      <div class="pay-card reveal">
        <div class="pay-brand pay-brand-lg" data-testid="pay-jazzcash">
          <img src="<?= asset('img/jazzcash.png') ?>" alt="JazzCash">
        </div>
        <h3>JazzCash</h3>
        <p>The largest mobile wallet in the region. Withdraw any time from <?= $currency ?> 500 upwards.</p>
        <div class="pay-meta"><i class="fa-solid fa-bolt"></i> Same-day payout</div>
      </div>
      <div class="pay-card reveal" style="--d:.08s">
        <div class="pay-brand pay-brand-lg" data-testid="pay-easypaisa">
          <img src="<?= asset('img/easypaisa.png') ?>" alt="EasyPaisa">
        </div>
        <h3>EasyPaisa</h3>
        <p>Telenor's nationwide wallet. Fast, secure transfers straight to your registered SIM.</p>
        <div class="pay-meta"><i class="fa-solid fa-shield-halved"></i> Bank-grade security</div>
      </div>
      <div class="pay-card reveal accent" style="--d:.16s">
        <div class="pay-logo grad"><i class="fa-solid fa-bolt"></i></div>
        <h3>More coming soon</h3>
        <p>SadaPay, NayaPay and direct bank transfers are on the way — vote for your favourite inside the app.</p>
        <div class="pay-meta"><i class="fa-solid fa-circle-info"></i> In development</div>
      </div>
    </div>
  </div>
</section>

<!-- ============================== TESTIMONIALS ============================== -->
<section class="lp-section">
  <div class="lp-container">
    <div class="lp-section-head reveal">
      <span class="kicker">Real members</span>
      <h2>From <span class="grad-text">side hustle</span> to <span class="grad-text">main income</span>.</h2>
    </div>
    <div class="testi-grid">
      <div class="testi-card reveal">
        <div class="testi-stars">★★★★★</div>
        <p>"I earn more in two hours on DYNOVA than I used to in a full day. The referral system is the real game-changer."</p>
        <div class="testi-who">
          <span class="av" style="background:linear-gradient(135deg,#3eb6ff,#8d5bff)">A</span>
          <div><b>Ahmed R.</b><small>Lahore · Gold rank</small></div>
        </div>
      </div>
      <div class="testi-card reveal" style="--d:.1s">
        <div class="testi-stars">★★★★★</div>
        <p>"Withdrawals are fast and the admins actually respond. After 3 months I'm hitting Silver salary every single week."</p>
        <div class="testi-who">
          <span class="av" style="background:linear-gradient(135deg,#ff5be0,#8d5bff)">S</span>
          <div><b>Sana M.</b><small>Karachi · Silver rank</small></div>
        </div>
      </div>
      <div class="testi-card reveal" style="--d:.2s">
        <div class="testi-stars">★★★★★</div>
        <p>"Started with zero investment. After 6 months my team is 80+ strong and I'm pulling Diamond monthly salary."</p>
        <div class="testi-who">
          <span class="av" style="background:linear-gradient(135deg,#3ddc97,#3eb6ff)">U</span>
          <div><b>Usman T.</b><small>Islamabad · Diamond rank</small></div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ============================== FAQ ============================== -->
<section id="faq" class="lp-section alt">
  <div class="lp-container">
    <div class="lp-section-head reveal">
      <span class="kicker">FAQ</span>
      <h2>Everything you wanted to ask.</h2>
    </div>
    <div class="faq-list reveal">
      <?php
      $faqs = [
        ['Is DYNOVA really free to join?', 'Yes. Sign-up takes 60 seconds and costs nothing. You can start rating videos and earning straight away — no deposit required.'],
        ['How much can I realistically earn?', 'With 25 daily tasks you can earn up to '.$currency.' 2,000/day from tasks alone. Active referrers add '.$currency.' 1,000–5,000+ daily on top, plus weekly rank salary.'],
        ['When and how do I get paid?', 'Withdraw any time you reach '.$currency.' 500. Funds are transferred to your JazzCash or EasyPaisa wallet — usually within a few hours after admin approval.'],
        ['How does the 3-level referral system work?', 'You earn 10% from people you directly invite (L1), 5% from people they invite (L2), and 2.5% from level 3. All credits are automatic and visible in your wallet.'],
        ['What about salary ranks?', 'Hit referral and team-business milestones to unlock Bronze / Silver / Gold / Diamond ranks. Each rank pays a fixed weekly salary on top of everything else.'],
        ['Is my data safe?', 'Absolutely. We never share your number, all logins are CSRF-protected and passwords are stored with industry-standard bcrypt hashing.'],
      ];
      foreach ($faqs as $i => $f): ?>
        <details class="faq-item" <?= $i === 0 ? 'open' : '' ?>>
          <summary>
            <span><?= e($f[0]) ?></span>
            <i class="fa-solid fa-chevron-down"></i>
          </summary>
          <p><?= e($f[1]) ?></p>
        </details>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ============================== FINAL CTA ============================== -->
<section class="lp-section">
  <div class="lp-container">
    <div class="cta-banner reveal">
      <div class="cta-glow"></div>
      <div class="cta-inner">
        <h2>Your next paycheck is one tap away.</h2>
        <p>Join thousands already earning daily on DYNOVA NETWORK. It takes 60 seconds — and the first task is on us.</p>
        <div class="cta-actions">
          <a href="<?= route_url('auth/signup') ?>" class="btn inline lg" data-testid="final-cta-signup">
            <i class="fa-solid fa-rocket"></i> Create my free account
          </a>
          <a href="<?= route_url('auth/login') ?>" class="btn ghost inline lg" data-testid="final-cta-login">
            I already have an account
          </a>
        </div>
      </div>
    </div>
  </div>
</section>
/section>
