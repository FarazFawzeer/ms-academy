<meta charset="utf-8" />
<title><?php echo e($subtitle ?? 'Dashboard'); ?> - <?php echo e(config('academy.name', 'MS Football Academy')); ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="<?php echo e(config('academy.name', 'MS Football Academy')); ?> management system: QR attendance, player profiles, fees, renewal reminders and coach salary." />
<meta name="author" content="<?php echo e(config('academy.name', 'MS Football Academy')); ?>" />
<meta http-equiv="X-UA-Compatible" content="IE=edge" />

<meta name="robots" content="noindex, nofollow" />
<meta name="theme-color" content="#07142b">

<!-- App favicon -->
<link rel="icon" type="image/png" href="<?php echo e(asset('images/ms-logo.png')); ?>">
<link rel="shortcut icon" href="<?php echo e(asset('images/ms-logo.png')); ?>">
<link rel="apple-touch-icon" href="<?php echo e(asset('images/app/apple-touch-icon.png')); ?>">

<!-- Home-screen app (Add to Home Screen / Install app): opens full screen with its own icon -->
<link rel="manifest" href="<?php echo e(asset('manifest.json')); ?>">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black">
<meta name="apple-mobile-web-app-title" content="MS Academy">
<meta name="application-name" content="MS Academy">
<script>
    // no caching, only makes the app installable and shows a "No internet" page (public/sw.js)
    if ('serviceWorker' in navigator && (location.protocol === 'https:' || location.hostname === 'localhost')) {
        window.addEventListener('load', function() {
            navigator.serviceWorker.register('<?php echo e(asset('sw.js')); ?>').catch(function() {});
        });
    }
</script><?php /**PATH F:\Nexas\ms-academy\resources\views/layouts/partials/title-meta.blade.php ENDPATH**/ ?>