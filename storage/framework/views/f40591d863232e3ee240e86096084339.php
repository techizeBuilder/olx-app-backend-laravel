


<?php
    $seoData = $seoTranslations[$lang->id] ?? [];
?>

<div class="seo-data-container">
    <div class="card mt-3">
        <div class="card-header p-2">
            <h6 class="mb-0"><?php echo e(__('SEO Details')); ?> (<?php echo e($lang->name); ?>)</h6>
        </div>
        <div class="card-body p-2">
            <div class="form-group">
                <label><?php echo e(__('Meta Title')); ?> (<?php echo e($lang->name); ?>)</label>
                <input type="text" name="meta_title[<?php echo e($lang->id); ?>]" class="form-control" value="<?php echo e($seoData['meta_title'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label><?php echo e(__('Meta Description')); ?> (<?php echo e($lang->name); ?>)</label>
                <textarea name="meta_description[<?php echo e($lang->id); ?>]" class="form-control" rows="3"><?php echo e($seoData['meta_description'] ?? ''); ?></textarea>
            </div>

            <div class="form-group">
                <label><?php echo e(__('Meta Keywords')); ?> (<?php echo e($lang->name); ?>)</label>
                <input type="text" name="meta_keywords[<?php echo e($lang->id); ?>]" class="tagify-input form-control" value="<?php echo e($seoData['meta_keywords'] ?? ''); ?>" placeholder="<?php echo e(__('Enter keywords...')); ?>">
            </div>

            <div class="form-group">
                <label><?php echo e(__('Schema')); ?> (<?php echo e($lang->name); ?>)</label>
                <textarea name="schema[<?php echo e($lang->id); ?>]" class="form-control" rows="4" placeholder='{"@context": "https://schema.org", ...}'><?php echo e($seoData['schema'] ?? ''); ?></textarea>
            </div>
        </div>
    </div>

</div>
<?php /**PATH C:\wamp64\www\OLX\resources\views/components/seo-fields.blade.php ENDPATH**/ ?>