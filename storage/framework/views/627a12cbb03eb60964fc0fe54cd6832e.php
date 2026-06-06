<?php $__env->startSection('title'); ?>
    <?php echo e(__('System Update')); ?>

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
        <div class="card">

            <form class="create-form" action="<?php echo e(route('system-update.index')); ?>" method="POST" enctype="multipart/form-data" data-success-function="successFunction">
                <?php echo e(csrf_field()); ?>

                <div class="card-body">
                    <div class="row mt-1">
                        <div class="card-body">
                            <label class="col-sm-12 col-form-label text-center"><b><?php echo e(__('System Version')); ?> : </b> <span class="text-danger"><?php echo e($system_version['value'] ?? '1.0.0'); ?></span></label>
                            <div class="form-group row mt-5">
                                <label for="purchase_code" class="col-sm-2 col-form-label text-center"><?php echo e(__('Purchase Code')); ?></label>
                                <div class="col-sm-3">
                                    <input id="purchase_code" required name="purchase_code" type="text" class="form-control">
                                </div>

                                <label class="col-sm-2 col-form-label text-center"><?php echo e(__('Update File')); ?></label>
                                <div class="col-sm-3">
                                    <input required name="file" type="file" class="form-control">
                                </div>
                                <div class="col-sm-2 d-flex justify-content-end">
                                    <button type="submit" name="btnAdd1" value="btnAdd" class="btn btn-primary me-1 mb-1"><?php echo e(__('Save')); ?></button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </section>
    <script>
        function successFunction() {
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        }
    </script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.main', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\wamp64\www\Admin Panel 2.12.0\Eclassify Version 2.12.0 Fresh Installation\resources\views/system-update/index.blade.php ENDPATH**/ ?>