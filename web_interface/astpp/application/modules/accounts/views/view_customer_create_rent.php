<?php extend('left_panel_master.php') ?>
<?php startblock('extra_head'); ?>
     <?php /*    <style type="text/css">
        .btn-group.bootstrap-select.col-md-5.form-control {
            margin-left: 40px;
        }
        .btn-group.bootstrap-select.col-md-5.form-control button, .dropdown-menu.open {
            width: 200px;
        }
    </style> */ ?>
<script>
    $(document).ready(function(){
      if (parseInt($('select[name=payment_type]').val()) === 2) {
        $('input[name=leftpayments]').closest('li').show();
      } else {
        $('input[name=leftpayments]').closest('li').hide();
      }
      $('select[name=payment_type]').on('change', function(){
         if (parseInt($(this).val()) === 2) {
           $('input[name=leftpayments]').closest('li').show();
        } else {
           $('input[name=leftpayments]').closest('li').hide();
         }
      })
    });
</script>
<?php endblock(); ?>

<?php startblock('page-title') ?>
<?php echo $title ?>
<?php endblock() ?>

<?php startblock('content') ?>   

<div id="main-wrapper" class="tabcontents">  
    <div id="content">   
        <div class="row"> 
            <div class="col-md-12 no-padding color-three border_box">

            </div>
            <div class="padding-15 col-md-12">
                <div class="slice color-three pull-left content_border">
                    <?php echo $form; ?>
                    <?php if (isset($validation_errors) && $validation_errors != '') { ?>
                        <script>
                            var ERR_STR = '<?php echo $validation_errors; ?>';
                            print_error(ERR_STR);
                        </script>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endblock() ?>    

<?php end_extend() ?>