<?php

function statutReclamationLabel(string $s): string {
    return match($s) {
        'open'     => '🟡 Ouverte',
        'closed'   => '🟢 Fermée',
        'rejected' => '🔴 Rejetée',
        'pending'  => '⏳ En attente',
        default    => $s
    };
}
