<?php

  $host = preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? '');
  $hostParts = explode('.', $host);
  $tld = count($hostParts) > 1 ? array_pop($hostParts) : '';
  $domain = implode('.', $hostParts);
  $fullDomain = $domain . ($tld !== '' ? '.' . $tld : '');

  $logPath = __DIR__ . '/interest.log';

  $viewLogKey = 'qq';
  if ($viewLogKey !== '' && isset($_GET['key']) && hash_equals($viewLogKey, $_GET['key'])) {
    header('Content-Type: text/plain; charset=UTF-8');
    echo is_file($logPath) ? file_get_contents($logPath) : "interest.log no existe todavía (nadie se ha apuntado aún).\n";
    exit;
  }

  $subscribeDone  = false;
  $subscribeError = '';
  $subscribeEmail = '';

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subscribeEmail = trim($_POST['email'] ?? '');
    if (!filter_var($subscribeEmail, FILTER_VALIDATE_EMAIL)) {
      $subscribeError = 'Escribe un email válido.';
    } elseif (
      is_file($logPath)
      && preg_match('/email=' . preg_quote($subscribeEmail, '/') . '(?=\s|$)/im', file_get_contents($logPath))
    ) {
      $subscribeError = 'Ese correo ya está apuntado — te avisaremos.';
    } else {
      $logLine = sprintf(
        "[%s] email=%s ip=%s\n",
        date('Y-m-d H:i:s'),
        $subscribeEmail,
        $_SERVER['REMOTE_ADDR'] ?? ''
      );
      $bytesWritten = @file_put_contents($logPath, $logLine, FILE_APPEND | LOCK_EX);
      if ($bytesWritten === false || $bytesWritten !== strlen($logLine)) {
          $subscribeError = 'No se pudo guardar tu email (fallo del servidor) — inténtalo de nuevo en un momento.';
      } else {
          $subscribeDone = true;
      }
    }
  }

  $bgUrls = [];
  $imgDir = __DIR__ . '/img';
  if (is_dir($imgDir)) {
    $allImages = glob($imgDir . '/*.{jpg,jpeg,JPG,JPEG,png,PNG}', GLOB_BRACE);
    if ($allImages) {
      shuffle($allImages);
      foreach (array_slice($allImages, 0, 10) as $path) {
        $bgUrls[] = '/coming-soon/img/' . rawurlencode(basename($path));
      }
    }
  }
  if (!$bgUrls) {
    $bgUrls[] = 'https://picsum.photos/1920/1080';
  }
  $bgUrl = $bgUrls[0];

  $logoPath = __DIR__ . '/logo.png';
  $logoUrl  = file_exists($logoPath) ? '/coming-soon/logo.png?' . filemtime($logoPath) : null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($host, ENT_QUOTES, 'UTF-8') ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body, html {
            height: 100%;
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        }
        .homepage {
            height: 100vh;
            display: grid;
            place-content: center;
            gap: 1.5rem;
            background: linear-gradient(rgba(0, 0, 0, 0.4), rgba(0, 0, 0, 0.4)),
                        url('<?= htmlspecialchars($bgUrl, ENT_QUOTES, 'UTF-8') ?>') no-repeat center center / cover;
        }
        .domain {
            color: #fff;
            font-size: clamp(2.5rem, 8vw, 7rem);
            font-weight: 200;
            letter-spacing: -0.02em;
            text-shadow: 0 8px 24px rgba(0, 0, 0, .5);
            text-align: center;
            padding: 0 20px;
            line-height: 0.9;
            overflow-wrap: break-word;
        }
        .brand-logo {
            display: block;
            max-width: 12rem;
            margin: 0 auto;
        }
        @media (max-width: 24rem) { .brand-logo { max-width: 50%; } }
        .info {
            color: #fff;
            font-size: clamp(1.1rem, 2.6vw, 1.6rem);
            font-weight: 300;
            text-shadow: 0 4px 12px rgba(0, 0, 0, .5);
            text-align: center;
            padding: 0 20px;
        }
        form {
            display: flex;
            gap: .5rem;
            justify-self: center;
            flex-wrap: wrap;
            justify-content: center;
            padding: 0 20px;
        }
        input[type="email"] {
            font: inherit;
            padding: .75rem 1rem;
            border-radius: .5rem;
            border: 1.5px solid rgba(255, 255, 255, .55);
            background: rgba(255, 255, 255, .1);
            color: #fff;
            min-width: 16rem;
        }
        input[type="email"]::placeholder { color: rgba(255, 255, 255, .7); }
        button {
            font: inherit;
            font-weight: 600;
            padding: .75rem 1.5rem;
            border-radius: .5rem;
            border: none;
            background: #fff;
            color: #111;
            cursor: pointer;
        }
        .status {
            color: #fff;
            text-align: center;
            font-size: .95rem;
            padding: 0 20px;
        }
        .status-success {
            color: #fff;
            text-align: center;
            font-size: clamp(1.3rem, 3vw, 2rem);
            font-weight: 600;
            line-height: 1.4;
            margin: 0 20px;
            max-width: 40rem;
            padding: 1.25rem 1.75rem;
            background: rgba(0, 0, 0, .65);
            border-radius: 1rem;
            backdrop-filter: blur(6px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, .4);
        }
    </style>
</head>
<body>
  <main class="homepage">
    <?php if ($logoUrl): ?>
      <img class="brand-logo" src="<?= htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($fullDomain, ENT_QUOTES, 'UTF-8') ?>">
    <?php else: ?>
      <h1 class="domain"><?= htmlspecialchars($fullDomain, ENT_QUOTES, 'UTF-8') ?></h1>
    <?php endif; ?>
    <?php if ($subscribeDone): ?>
      <p class="status-success">🤩 ¡Recibido! Te avisaremos en cuanto esté listo.</p>
    <?php else: ?>
      <p class="info">coming soon</p>
      <form method="post">
        <input type="email" name="email" placeholder="tu@correo.com" required value="<?= htmlspecialchars($subscribeEmail, ENT_QUOTES, 'UTF-8') ?>">
        <button type="submit">Avísame</button>
      </form>
      <?php if ($subscribeError): ?>
        <p class="status"><?= htmlspecialchars($subscribeError, ENT_QUOTES, 'UTF-8') ?></p>
      <?php endif; ?>
    <?php endif; ?>
  </main>
</body>
</html>