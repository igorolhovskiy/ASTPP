<?php extend('left_panel_master.php') ?>
<?php startblock('extra_head') ?>
<script type="text/javascript">
    $(document).ready(function(event){
        var productUrl = "<?php echo base_url() . "accounts/customer_products/{$user_id}/" ?>";
        build_grid("rents", productUrl, <?php echo $grid_fields ?> , <?php echo $grid_buttons ?>);
        $('.checkall').click(function () {
            $('.chkRefNos').attr('checked', this.checked); //if you want to select/deselect checkboxes use this
        });
        $("#account_search_btn").click(function(){
            post_request_for_search("rents", productUrl, "products_search");
        });        
        $("#id_reset").click(function(){ 
            clear_search_request("rents", productUrl);
        });
         $("#batch_update_btn").click(function(){
            submit_form("reseller_batch_update");
        })
        
        $("#batch_update_btn").click(function(){
            submit_form("customer_batch_update");
        })
        
        $("#id_batch_reset").click(function(){ 
            $(".update_drp").each(function(){
                var inputid = this.name.split("[");
                $('#'+inputid[0]).hide();
            });
        });
        $(".update_drp").change(function(){
           var inputid = this.name.split("[");
           if(this.value != "1"){
               $('#'+inputid[0]).show();
           }else{
               $('#'+inputid[0]).hide();
           }
        }).each(function(){
            var inputid = this.name.split("[");
            if(this.value != "1"){
                $('#'+inputid[0]).show();
            }else{
                $('#'+inputid[0]).hide();
            }
        });
    });
</script>
<?php endblock() ?>

<?php startblock('page-title') ?>
<?php echo $page_title ?>
<?php endblock() ?>

<?php startblock('content') ?>   
<div id="main-wrapper" class="tabcontents">
    <div id="content">   
        <div class="row"> 
            <div class="col-md-12 no-padding color-three border_box">
                <div class="pull-left">
                    <ul class="breadcrumb">
                        <li><a href="<?= base_url()."accounts/".strtolower($accounttype)."_list/"; ?>"><?= ucfirst($accounttype); ?>s </a></li>
                        <li>
                            <a href="<?= base_url()."accounts/".strtolower($accounttype)."_edit/".$user_id."/"; ?>"> Profile </a>
                        </li>
                        <li class="active">
                            <a href="<?= base_url()."accounts/".strtolower($accounttype)."_products/".$user_id."/"; ?>">
                                Products
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="pull-right">
                    <ul class="breadcrumb">
                        <li class="active pull-right">
                            <a href="<?= base_url()."accounts/".strtolower($accounttype)."_edit/".$user_id."/"; ?>"> <i class="fa fa-fast-backward" aria-hidden="true"></i> Back</a></li>
                    </ul>
                </div>
            </div>
            <div class="padding-15 col-md-12">
                <div class="col-md-12 no-padding">
                    <div id="show_search" class="pull-right margin-t-10 col-md-4 no-padding">
                    </div>
                </div> 
                <div class="col-md-12 no-padding">
                    <div class="col-md-12 color-three padding-b-20 slice color-three pull-left content_border">
                        <form method="POST" action="del/0/" enctype="multipart/form-data" id="ListForm">
                            <table id="rents" align="left" style="display:none;"></table>
                        </form>
                    </div>   
                </div>
            </div>
        </div>
    </div>
</div>
<?php endblock() ?>    

<?php end_extend() ?>