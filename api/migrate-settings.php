<?php
// One-time migration — seeds the settings content blocks. Delete after running.
require_once __DIR__ . '/config/database.php';

$db = getDB();

$rows = [
  ['settings','seo_title','Health Systems Initiative | Building Health Systems. Saving Lives.'],
  ['settings','seo_description','HSI is a non-profit strengthening health systems through effective, equitable, and sustainable digital technologies — improving coordination, communication, and access to quality healthcare.'],
  ['settings','seo_keywords','health systems strengthening, digital health Africa, healthcare coordination Nigeria, health equity, digital health non-profit, interoperability, health information systems'],
  ['settings','og_image',''],
  ['settings','site_url','https://healthsystemsinitiative.org'],
  ['settings','ga_id',''],
  ['settings','cookie_tool',''],
  ['settings','cookie_script',''],
  ['settings','info_email','info@healthsystemsinitiative.org'],
  ['settings','form_recipient',''],
  ['settings','smtp_host',''],
  ['settings','smtp_port','465'],
  ['settings','smtp_user',''],
  ['settings','smtp_pass',''],
  ['settings','smtp_from',''],
  ['settings','linkedin',''],
  ['settings','twitter',''],
  ['settings','facebook',''],
  ['settings','youtube',''],
  ['settings','social_other',''],
  ['settings','donorbox_url',''],
  ['settings','paystack_url',''],
];

$stmt = $db->prepare('INSERT INTO content_blocks (page, block_key, value) VALUES (?,?,?) ON CONFLICT (page, block_key) DO NOTHING');
foreach ($rows as [$page, $key, $val]) {
    $stmt->execute([$page, $key, $val]);
}

echo '<h2 style="font-family:sans-serif;color:#065F46">✓ Settings rows seeded successfully</h2>';
echo '<p style="font-family:sans-serif"><a href="../admin/">Go to Admin Panel → Settings</a></p>';
echo '<p style="font-family:sans-serif;color:#991B1B"><strong>Delete this file after use.</strong></p>';
