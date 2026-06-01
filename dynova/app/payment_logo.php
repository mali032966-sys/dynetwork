<?php
/**
 * Map a payment-method name to a built-in logo image asset (in /assets/img/).
 * Returns null if no asset is bundled — caller should fall back to a letter pill.
 */
function payment_logo_asset(string $name): ?string {
    $n = strtolower(trim($name));
    if (strpos($n, 'jazz') !== false)   return asset('img/jazzcash.png');
    if (strpos($n, 'easy') !== false)   return asset('img/easypaisa.png');
    if (strpos($n, 'paisa') !== false)  return asset('img/easypaisa.png');
    return null;
}

/** Returns true if the given method name should render as a bank-card style logo. */
function payment_is_bank(string $name): bool {
    $n = strtolower(trim($name));
    return strpos($n, 'bank') !== false
        || strpos($n, 'iban') !== false
        || strpos($n, 'transfer') !== false;
}

/**
 * Render a small payment-method logo block — uses the bundled PNG when
 * we recognise the brand, otherwise falls back to a tinted letter pill.
 *
 * $variant: 'sm' (40px), 'md' (52px), 'lg' (72px)
 */
function payment_logo_html(string $name, string $variant = 'md'): string {
    $sizes = ['sm' => 40, 'md' => 52, 'lg' => 72];
    $px = $sizes[$variant] ?? $sizes['md'];
    $img = payment_logo_asset($name);
    if ($img) {
        return '<div class="pay-brand" data-variant="' . e($variant) . '"' .
               ' style="width:' . $px . 'px;height:' . $px . 'px">' .
               '<img src="' . e($img) . '" alt="' . e($name) . '"></div>';
    }
    // Bank-style logo (no PNG bundled, use a credit-card / bank icon pill)
    if (payment_is_bank($name)) {
        return '<div class="pm-logo pm-logo-bank" style="width:' . $px . 'px;height:' . $px . 'px;font-size:' . round($px * .40) . 'px">' .
               '<i class="fa-solid fa-building-columns"></i></div>';
    }
    $letter = strtoupper(substr($name, 0, 1));
    return '<div class="pm-logo" style="width:' . $px . 'px;height:' . $px . 'px;font-size:' . round($px * .42) . 'px">' .
           e($letter) . '</div>';
}
