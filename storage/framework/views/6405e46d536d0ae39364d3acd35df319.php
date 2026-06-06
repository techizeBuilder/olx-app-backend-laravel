<?php $__env->startSection('title'); ?>
    <?php echo e(__('Settings')); ?>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('page-title'); ?>
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h4><?php echo $__env->yieldContent('title'); ?></h4>
            </div>
            <div class="col-12 col-md-6 order-md-2 order-first"></div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <section class="section">
        <div class="row">
            <div class="col-xxl-3 col-xl-4 col-lg-6 col-md-12 mb-3">
                <a href="<?php echo e(route('settings.system')); ?>" class="card setting_active_tab h-100" style="text-decoration: none;">
                    <div class="content d-flex h-100">
                        <div class="row mx-2 ">
                            <div class="provider_a test">
                                <i class="fas fa-cogs text-dark icon_font_size "></i>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <h5 class="title"><?php echo e(__('Settings')); ?></h5>
                        <div class=""><?php echo e(__('Go to settings')); ?> <i class="fas fa-arrow-right mt-2 arrow_icon"></i>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-xxl-3 col-xl-4 col-lg-6 col-md-12 mb-3">
                <a href="<?php echo e(route('settings.web-settings')); ?>" class="card setting_active_tab h-100" style="text-decoration: none;">
                    <div class="content d-flex h-100">
                        <div class="row mx-2 ">
                            <div class="provider_a test">
                                <i class="fas fa-cog text-dark icon_font_size "></i>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <h5 class="title"><?php echo e(__('Web Settings')); ?></h5>
                        <div class=""><?php echo e(__('Go to settings')); ?> <i class="fas fa-arrow-right mt-2 arrow_icon"></i>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-xxl-3 col-xl-4 col-lg-6 col-md-12 mb-3">
                <a href="<?php echo e(route('settings.notification-setting')); ?>" class="card setting_active_tab h-100" style="text-decoration: none;">
                    <div class="content d-flex h-100">
                        <div class="row mx-2 ">
                            <div class="provider_a test">
                                <i class="fas fa-bell text-dark icon_font_size "></i>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <h5 class="title"><?php echo e(__('Notification Settings')); ?></h5>
                        <div class=""><?php echo e(__('Go to settings')); ?> <i class="fas fa-arrow-right mt-2 arrow_icon"></i>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-xxl-3 col-xl-4 col-lg-6 col-md-12 mb-3">
                <a href="<?php echo e(route('settings.watermark-settings')); ?>" class="card setting_active_tab h-100" style="text-decoration: none;">
                    <div class="content d-flex h-100">
                        <div class="row mx-2 ">
                            <div class="provider_a test">
                                <i class="fas fa-solid fa-image text-dark icon_font_size "></i>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <h5 class="title"><?php echo e(__('Watermark Settings')); ?></h5>
                        <div class=""><?php echo e(__('Go to settings')); ?> <i class="fas fa-arrow-right mt-2 arrow_icon"></i>
                        </div>
                    </div>
                </a>
            </div>
            
            <div class="col-xxl-3 col-xl-4 col-lg-6 col-md-12 mb-3">
                <a href="<?php echo e(route('settings.login-method')); ?>" class="card setting_active_tab h-100" style="text-decoration: none;">
                    <div class="content d-flex h-100">
                        <div class="row mx-2 ">
                            <div class="provider_a test">
                            <i class="fa fa-envelope text-dark icon_font_size"></i>

                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <h5 class="title"><?php echo e(__('OTP Provider Settings')); ?></h5>
                        <div class=""><?php echo e(__('Go to settings')); ?> <i class="fas fa-arrow-right mt-2 arrow_icon"></i>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-xxl-3 col-xl-4 col-lg-6 col-md-12 mb-3">
                <a href="<?php echo e(route('settings.dummy-data.index')); ?>" class="card setting_active_tab h-100" style="text-decoration: none;">
                    <div class="content d-flex h-100">
                        <div class="row mx-2 ">
                            <div class="provider_a test">
                                <i class="fas fa-database text-dark icon_font_size"></i>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <h5 class="title"><?php echo e(__('Import Dummy Data')); ?></h5>
                        <div class=""><?php echo e(__('Add dummy categories and custom fields')); ?> <i class="fas fa-arrow-right mt-2 arrow_icon"></i>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-xxl-3 col-xl-4 col-lg-6 col-md-12 mb-3">
                <a href="<?php echo e(route('settings.admob.index')); ?>" class="card setting_active_tab h-100" style="text-decoration: none;">
                    <div class="content d-flex h-100">
                        <div class="row mx-2 ">
                            <div class="provider_a test   ">
                                <i class="fas fa-ad text-dark icon_font_size"></i>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <h5 class="title"><?php echo e(__('Admob')); ?></h5>
                        <div class=""><?php echo e(__('Go to settings')); ?> <i class="fas fa-arrow-right mt-2 arrow_icon"></i>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-xxl-3 col-xl-4 col-lg-6 col-md-12 mb-3">
                <a href="<?php echo e(route('settings.adsense.index')); ?>" class="card setting_active_tab h-100" style="text-decoration: none;">
                    <div class="content d-flex h-100">
                        <div class="row mx-2 ">
                            <div class="provider_a test">
                                <i class="fab fa-google text-dark icon_font_size"></i>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <h5 class="title"><?php echo e(__('AdSense')); ?></h5>
                        <div class=""><?php echo e(__('Go to settings')); ?> <i class="fas fa-arrow-right mt-2 arrow_icon"></i>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-xxl-3 col-xl-4 col-lg-6 col-md-12 mb-3">
                <a href="<?php echo e(route('settings.about-us.index')); ?>" class="card setting_active_tab h-100" style="text-decoration: none;">
                    <div class="content d-flex h-100">
                        <div class="row mx-2 ">
                            <div class="provider_a test   ">
                                <i class="fas fa-info-circle text-dark icon_font_size"></i>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <h5 class="title"><?php echo e(__('About Us')); ?></h5>
                        <div class=""><?php echo e(__('Go to settings')); ?> <i class="fas fa-arrow-right mt-2 arrow_icon"></i>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-xxl-3 col-xl-4 col-lg-6 col-md-12 mb-3">
                <a href="<?php echo e(route('settings.terms-conditions.index')); ?>" class="card setting_active_tab h-100"
                   style="text-decoration: none;">
                    <div class="content d-flex h-100">
                        <div class="row mx-2 ">
                            <div class="provider_a test   ">
                                <i class=" fas fa-file-contract text-dark icon_font_size"></i>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <h5 class="title"><?php echo e(__('Terms & Conditions')); ?></h5>
                        <div class=""><?php echo e(__('Go to settings')); ?> <i class="fas fa-arrow-right mt-2 arrow_icon"></i>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-xxl-3 col-xl-4 col-lg-6 col-md-12 mb-3">
                <a href="<?php echo e(route('settings.privacy-policy.index')); ?>" class="card setting_active_tab h-100" style="text-decoration: none;">
                    <div class="content d-flex h-100">
                        <div class="row mx-2 ">
                            <div class="provider_a test">
                                <i class=" fas fas fa-shield-alt text-dark icon_font_size"></i>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <h5 class="title"><?php echo e(__('Privacy Policy')); ?></h5>
                        <div class="<?php echo e(route('settings.privacy-policy.index')); ?>"><?php echo e(__('Go to settings')); ?> <i class="fas fa-arrow-right mt-2 arrow_icon"></i></div>
                    </div>

                </a>
            </div>
            <div class="col-xxl-3 col-xl-4 col-lg-6 col-md-12 mb-3">
                <a href="<?php echo e(route('settings.refund-policy.index')); ?>" class="card setting_active_tab h-100" style="text-decoration: none;">
                    <div class="content d-flex h-100">
                        <div class="row mx-2 ">
                            <div class="provider_a test">
                                <i class=" fas fas fa-shield-alt text-dark icon_font_size"></i>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <h5 class="title"><?php echo e(__('Refund Policy')); ?></h5>
                        <div class="<?php echo e(route('settings.refund-policy.index')); ?>"><?php echo e(__('Go to settings')); ?> <i class="fas fa-arrow-right mt-2 arrow_icon"></i></div>
                    </div>

                </a>
            </div>
            <div class="col-xxl-3 col-xl-4 col-lg-6 col-md-12 mb-3">
                <a href="<?php echo e(route('settings.contact-us.index')); ?>" class="card setting_active_tab h-100" style="text-decoration: none;">
                    <div class="content d-flex h-100">
                        <div class="row mx-2 ">
                            <div class="provider_a test">
                                <i class=" fas fas fa-address-book text-dark icon_font_size"></i>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <h5 class="title"><?php echo e(__('Contact Us')); ?></h5>
                        <div class="<?php echo e(route('settings.contact-us.index')); ?>"><?php echo e(__('Go to settings')); ?> <i class="fas fa-arrow-right mt-2 arrow_icon"></i></div>
                    </div>

                </a>
            </div>

            <div class="col-xxl-3 col-xl-4 col-lg-6 col-md-12 mb-3">
                <a href="<?php echo e(route('settings.firebase.index')); ?>" class="card setting_active_tab h-100"
                   style="text-decoration: none;">
                    <div class="content d-flex h-100">
                        <div class="row mx-2 ">
                            <div class="provider_a test   ">
                                <i class=" fas fa-cloud text-dark icon_font_size"></i>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <h5 class="title"><?php echo e(__('Firebase Settings')); ?></h5>
                        <div class=""><?php echo e(__('Go to settings')); ?> <i class="fas fa-arrow-right mt-2 arrow_icon"></i>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-xxl-3 col-xl-4 col-lg-6 col-md-12 mb-3">
                <a href="<?php echo e(route('settings.language.index')); ?>" class="card setting_active_tab h-100"
                   style="text-decoration: none;">
                    <div class="content d-flex h-100">
                        <div class="row mx-2 ">
                            <div class="provider_a test   ">
                                <i class=" fas fas fa-language text-dark icon_font_size"></i>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <h5 class="title"><?php echo e(__('Languages')); ?></h5>
                        <div class=""><?php echo e(__('Go to settings')); ?> <i class="fas fa-arrow-right mt-2 arrow_icon"></i>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-xxl-3 col-xl-4 col-lg-6 col-md-12 mb-3">
                <a href="<?php echo e(route('settings.payment-gateway.index')); ?>" class="card setting_active_tab h-100" style="text-decoration: none;">
                    <div class="content d-flex h-100">
                        <div class="row mx-2 ">
                            <div class="provider_a test   ">
                                <i class="fas fa-dollar-sign text-dark icon_font_size"></i>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <h5 class="title"><?php echo e(__('Payment Gateways')); ?></h5>
                        <div class=""><?php echo e(__('Go to settings')); ?> <i class="fas fa-arrow-right mt-2 arrow_icon"></i>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-xxl-3 col-xl-4 col-lg-6 col-md-12 mb-3">
                <a href="<?php echo e(route('settings.system-status.index')); ?>" class="card setting_active_tab h-100" style="text-decoration: none;">
                    <div class="content d-flex h-100">
                        <div class="row mx-2 ">
                            <div class="provider_a test   ">
                                <i class="fas fa-external-link-square-alt text-dark icon_font_size"></i>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <h5 class="title"><?php echo e(__('System Status')); ?></h5>
                        <div class=""><?php echo e(__('Go to settings')); ?> <i class="fas fa-arrow-right mt-2 arrow_icon"></i>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-xxl-3 col-xl-4 col-lg-6 col-md-12 mb-3">
                <a href="<?php echo e(route('settings.seo-settings.index')); ?>" class="card setting_active_tab h-100"
                   style="text-decoration: none;">
                    <div class="content d-flex h-100">
                        <div class="row mx-2 ">
                            <div class="provider_a test   ">
                                <i class="fab fa-searchengin text-dark icon_font_size"></i>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <h5 class="title"><?php echo e(__('Seo-Settings')); ?></h5>
                        <div class=""><?php echo e(__('Go to settings')); ?> <i class="fas fa-arrow-right mt-2 arrow_icon"></i>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-xxl-3 col-xl-4 col-lg-6 col-md-12 mb-3">
                <a href="<?php echo e(route('settings.file-manager.index')); ?>" class="card setting_active_tab h-100"
                   style="text-decoration: none;">
                    <div class="content d-flex h-100">
                        <div class="row mx-2 ">
                            <div class="provider_a test">
                                <FontAwesomeIcon icon="" />
                                <i class="fas fa-file-export text-dark icon_font_size"></i>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <h5 class="title"><?php echo e(__('File Manager')); ?></h5>
                        <div class=""><?php echo e(__('Go to settings')); ?> <i class="fas fa-arrow-right mt-2 arrow_icon"></i>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-xxl-3 col-xl-4 col-lg-6 col-md-12 mb-3">
                <a href="<?php echo e(route('settings.email-templates.index')); ?>" class="card setting_active_tab h-100"
                   style="text-decoration: none;">
                    <div class="content d-flex h-100">
                        <div class="row mx-2 ">
                            <div class="provider_a test">
                                <i class="fas fa-envelope text-dark icon_font_size"></i>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <h5 class="title"><?php echo e(__('Email Templates')); ?></h5>
                        <div class=""><?php echo e(__('Manage email templates')); ?> <i class="fas fa-arrow-right mt-2 arrow_icon"></i>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-xxl-3 col-xl-4 col-lg-6 col-md-12 mb-3">
                <a href="<?php echo e(route('settings.gemini-settings')); ?>" class="card setting_active_tab h-100"
                   style="text-decoration: none;">
                    <div class="content d-flex h-100">
                        <div class="row mx-2 ">
                            <div class="provider_a test">
                                <i class="fas fa-robot text-dark icon_font_size"></i>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <h5 class="title"><?php echo e(__('Gemini AI')); ?></h5>
                        <div class=""><?php echo e(__('AI content generation settings')); ?> <i class="fas fa-arrow-right mt-2 arrow_icon"></i>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-xxl-3 col-xl-4 col-lg-6 col-md-12 mb-3">
                <a href="<?php echo e(route('settings.default-currency.index')); ?>" class="card setting_active_tab h-100"
                   style="text-decoration: none;">
                    <div class="content d-flex h-100">
                        <div class="row mx-2 ">
                            <div class="provider_a test">
                                <FontAwesomeIcon icon="" />
                                <i class="fas bi-cash-stack text-dark icon_font_size"></i>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <h5 class="title"><?php echo e(__('Default Currency Settings')); ?></h5>
                        <div class=""><?php echo e(__('Go to settings')); ?> <i class="fas fa-arrow-right mt-2 arrow_icon"></i>
                        </div>
                    </div>
                </a>
            </div>

            <?php if (\Illuminate\Support\Facades\Blade::check('hasrole', 'Super Admin')): ?>
            <div class="col-xxl-3 col-xl-4 col-lg-6 col-md-12 mb-3">
                <a href="<?php echo e(route('settings.error-logs.index')); ?>" class="card setting_active_tab h-100" style="text-decoration: none;">
                    <div class="content d-flex h-100">
                        <div class="row mx-2 ">
                            <div class="provider_a test   ">
                                <i class="fa fa-file-alt text-dark icon_font_size"></i>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <h5 class="title"><?php echo e(__('Log Viewer')); ?></h5>
                        <div class=""><?php echo e(__('Find Errors in your System')); ?> <i class="fas fa-arrow-right mt-2 arrow_icon"></i>
                        </div>
                    </div>
                </a>
            </div>
            <?php endif; ?>
        </div>
    </section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.main', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\wamp64\www\Admin Panel 2.12.0\Eclassify Version 2.12.0 Fresh Installation\resources\views/settings/index.blade.php ENDPATH**/ ?>