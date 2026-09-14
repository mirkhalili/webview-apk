<?php
declare(strict_types=1);

$config = require __DIR__ . '/../config/config.php';
date_default_timezone_set($config['app']['timezone']);
session_name($config['security']['session_name']);
session_set_cookie_params(['httponly'=>true,'secure'=>!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off','samesite'=>'Lax']);
session_start();

$pdo = new PDO($config['db']['dsn'], $config['db']['user'], $config['db']['pass'], $config['db']['options']);

function db(): PDO { global $pdo; return $pdo; }
function e(?string $v): string { return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }
function csrf_token(): string { if (empty($_SESSION['_csrf'])) $_SESSION['_csrf']=bin2hex(random_bytes(32)); return $_SESSION['_csrf']; }
function verify_csrf(): void { if (!hash_equals($_SESSION['_csrf'] ?? '', $_POST['_csrf'] ?? '')) { http_response_code(419); exit('درخواست نامعتبر است.'); } }
function user(): ?array { return $_SESSION['user'] ?? null; }
function require_login(): void { if (!user()) { header('Location: login.php'); exit; } }
function require_role(array $roles): void { require_login(); if (!in_array(user()['role'] ?? '', $roles, true)) { http_response_code(403); exit('دسترسی غیرمجاز'); } }
function audit(string $action, ?string $entityType=null, ?string $entityId=null, array $details=[]): void {
 $st=db()->prepare('INSERT INTO audit_logs(user_id,action,entity_type,entity_id,ip_address,user_agent,details) VALUES(?,?,?,?,?,?,?)');
 $st->execute([user()['id'] ?? null,$action,$entityType,$entityId,$_SERVER['REMOTE_ADDR'] ?? null,substr($_SERVER['HTTP_USER_AGENT'] ?? '',0,500),json_encode($details,JSON_UNESCAPED_UNICODE)]);
}
