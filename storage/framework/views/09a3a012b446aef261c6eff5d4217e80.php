<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title>Installation - <?php echo e(config('app.name', 'Laravel')); ?></title>
    <link rel="shortcut icon" href="<?php echo e(config('installer.icon')); ?>">
    <link href="<?php echo e(asset('vendor/installer/styles.css')); ?>" rel="stylesheet">
</head>
<body class="min-h-screen h-full w-full bg-cover bg-no-repeat bg-center flex" style="background-image: url('<?php echo e(config('installer.background')); ?>');">
<div class="py-12 sm:px-12 w-full max-w-5xl m-auto">
    <div class="w-full bg-white shadow sm:rounded-lg">
        <div class="px-4 py-8 border-b border-gray-200 sm:px-6">
            <div class="flex justify-center items-center">
                <img alt="App logo" class="h-12" src="<?php echo e(config('installer.icon')); ?>">
                <h2 class="pl-6 uppercase font-medium text-2xl text-gray-800"><?php echo e(config('app.name', 'Laravel')); ?> Installation</h2>
            </div>
        </div>
        <div class="px-4 py-5 sm:px-6 w-full">
            <?php echo $__env->yieldContent('step'); ?>
        </div>
    </div>
</div>
<style>
    @keyframes custom-spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    .custom-spin {
        animation: custom-spin 1s linear infinite;
    }
</style>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            form.addEventListener('submit', function (e) {
                const submitButton = form.querySelector('button[type="submit"]');
                if (submitButton) {
                    if (form.getAttribute('data-submitting') === 'true') {
                        e.preventDefault();
                        return;
                    }
                    form.setAttribute('data-submitting', 'true');
                    submitButton.disabled = true;
                    submitButton.classList.add('opacity-50', 'cursor-not-allowed');
                    submitButton.innerHTML = `Processing... <svg class="custom-spin ml-3 h-5 w-5 text-white inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>`;
                }
            });
        });
    });
</script>
</body>
</html>
<?php /**PATH C:\wamp64\www\Admin Panel 2.12.0\Eclassify Version 2.12.0 Fresh Installation\resources\views/vendor/installer/install.blade.php ENDPATH**/ ?>