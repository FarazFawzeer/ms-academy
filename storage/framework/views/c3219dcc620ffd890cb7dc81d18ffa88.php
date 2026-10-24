<?php echo app('Illuminate\Foundation\Vite')('resources/js/app.js'); ?>


<?php echo $__env->make('partials.date-picker', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

<?php echo $__env->yieldContent('scripts'); ?>
<?php /**PATH F:\Nexas\ms-academy\resources\views/layouts/partials/vendor-scripts.blade.php ENDPATH**/ ?>