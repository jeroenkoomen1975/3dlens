<?php
declare(strict_types=1);
if (!defined('THREEDLENS_BOOTSTRAPPED')) {
    http_response_code(404);
    exit;
}
function auth_page(string $title, string $intro, string $error, string $fields, string $submit): never
{
    $token = h(csrf_token());
    header('Content-Type: text/html; charset=utf-8');
    ?>
<!doctype html>
<html lang="nl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex,nofollow">
  <title><?= h($title) ?> · 3DLens</title>
  <style>
    :root { color-scheme: dark; font: 16px/1.6 system-ui, sans-serif; color: #e8e6df; background: #0d0e0c; }
    * { box-sizing: border-box; }
    body { min-height: 100svh; margin: 0; display: grid; place-items: center; padding: 24px; background-image: linear-gradient(#ffffff04 1px, transparent 1px), linear-gradient(90deg,#ffffff04 1px,transparent 1px); background-size: 32px 32px; }
    main { width: min(100%,440px); background: #1a1c19; border: 1px solid #3d3f38; padding: clamp(24px,5vw,40px); border-radius: 16px; box-shadow: 0 24px 90px #0006; }
    .brand { font-size: 26px; font-weight: 800; letter-spacing: -.05em; color: #d4ff3a; }
    .label { color: #a7ab9e; font-size: 12px; text-transform: uppercase; letter-spacing: .16em; margin: 4px 0 24px; }
    h1 { font-size: 24px; line-height: 1.2; margin: 0 0 12px; }
    p { color: #b4b6ac; margin: 0 0 24px; }
    label { display: block; margin: 18px 0 6px; font-size: 14px; }
    input { width: 100%; font: inherit; padding: 12px; color: #e8e6df; border: 1px solid #4e5246; background: #0d0e0c; border-radius: 6px; }
    input:focus { outline: 2px solid #d4ff3a; outline-offset: 2px; }
    button { width: 100%; margin-top: 26px; padding: 13px; border: 0; border-radius: 6px; background: #d4ff3a; color: #171a10; font: inherit; font-weight: 700; cursor: pointer; }
    .error { padding: 12px; margin-bottom: 20px; background: #ff5c2a16; border: 1px solid #ff5c2a80; border-radius: 6px; color: #ffc6b5; }
    small { display: block; margin-top: 20px; color: #a7ab9e; }
  </style>
</head>
<body>
<main>
  <div class="brand">◉ 3DLens</div>
  <div class="label">Jouw privé 3D-archief</div>
  <h1><?= h($title) ?></h1>
  <p><?= h($intro) ?></p>
  <?php if ($error !== ''): ?><div class="error" role="alert"><?= h($error) ?></div><?php endif; ?>
  <form method="post">
    <input type="hidden" name="csrf" value="<?= $token ?>">
    <?= $fields ?>
    <button type="submit"><?= h($submit) ?></button>
  </form>
  <small>Dit archief is alleen toegankelijk voor de eigenaar.</small>
</main>
</body>
</html>
    <?php
    exit;
}
