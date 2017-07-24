<?php extend('master.php') ?>

<?php startblock('extra_head') ?>
<?php endblock() ?>

<?php startblock('page-title') ?>
<?php echo "Create Product" ?>
<?php endblock() ?>

<?php startblock('content') ?>
<div class="container">
    <div class="row">
        <section class="slice color-three">
            <div class="w-section inverse no-padding">
                <?php echo $form; ?>
                <?php if (isset($validation_errors) && $validation_errors != '') { ?>
                    <script>
                        var ERR_STR = '<?php echo $validation_errors; ?>';
                        print_error(ERR_STR);
                    </script>
                <? } ?>
            </div>  
        </section>
    </div>
</div>
<?php endblock() ?>

<?php end_extend() ?>