<?php
$dir = __DIR__;
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));

$target_files = [
    'client.html', 'contrat.php', 'contratshow.php', 'contrat_auto.php',
    'contrat_habitation.php', 'contrat_protection.php', 'contrat_sante.php',
    'contrat_update_client.php', 'mes-sinistres.html', 'monprofile.html',
    'paiements.html', 'reseau.html', 'agences.html', 'reclamations.html', 'nos-offres.html'
];

foreach ($files as $file) {
    if (!$file->isFile()) continue;
    $filename = $file->getFilename();
    if (!in_array($filename, $target_files)) continue;

    $path = $file->getPathname();
    $content = file_get_contents($path);
    $original_content = $content;

    // Fix IDs
    $content = preg_replace('/<div\s+class="ptx-avatar-inner"[^>]*>.*?<\/div>/is', '<div class="ptx-avatar-inner" id="avatarInitials">..</div>', $content);
    $content = preg_replace('/<div\s+class="dropdown-avatar"[^>]*>.*?<\/div>/is', '<div class="dropdown-avatar" id="dropdownAvatar">..</div>', $content);
    $content = preg_replace('/<div\s+class="dropdown-name"[^>]*>.*?<\/div>/is', '<div class="dropdown-name" id="dropdownName">Chargement...</div>', $content);
    $content = preg_replace('/<div\s+class="dropdown-email"[^>]*>.*?<\/div>/is', '<div class="dropdown-email" id="dropdownEmail">...</div>', $content);
    $content = preg_replace('/<span\s+class="dropdown-role"[^>]*>.*?<\/span>/is', '<span class="dropdown-role" id="dropdownRole">Client</span>', $content);

    // Append main.js if missing
    if (strpos($content, 'assets/js/main.js') === false) {
        // Try replacing </body>
        if (stripos($content, '</body>') !== false) {
            $content = preg_replace('/<\/body>/i', '<script src="assets/js/main.js"></script>' . "\n" . '</body>', $content);
        } else {
            // Append at end if </body> missing
            $content .= "\n" . '<script src="assets/js/main.js"></script>';
        }
    }

    if ($content !== $original_content) {
        file_put_contents($path, $content);
        echo "Updated: $filename\n";
    }
}
echo "Done.\n";

