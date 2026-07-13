<!DOCTYPE html>
<html lang="<?php echo e(app()->getLocale()); ?>" <?php if(!empty($lang) && $lang->rtl): ?> dir="rtl" <?php endif; ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="<?php echo e($favicon ?? url('assets/images/logo/favicon.png')); ?>" type="image/x-icon">
    <title><?php echo $__env->yieldContent('title'); ?> || <?php echo e(config('app.name')); ?></title>
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>"/>
    <?php echo $__env->make('layouts.include', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    <?php echo $__env->yieldContent('css'); ?>
</head>
<body>
<div id="app">
    <?php echo $__env->make('layouts.sidebar', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    <div id="main" class='layout-navbar'>
        <?php echo $__env->make('layouts.topbar', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
        <div id="main-content">
            <div class="page-heading">
                <?php echo $__env->yieldContent('page-title'); ?>
            </div>
            <?php echo $__env->yieldContent('content'); ?>
        </div>
    </div>
    <div class="wrapper mt-5">
        <div class="content">
            <?php echo $__env->make('layouts.footer', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
        </div>
    </div>
</div>
<?php echo $__env->make('layouts.footer_script', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
<?php echo $__env->yieldContent('js'); ?>
<?php echo $__env->yieldContent('script'); ?>
</body>
</html>
<?php /**PATH C:\wamp64\www\OLX\resources\views/layouts/main.blade.php ENDPATH**/ ?>