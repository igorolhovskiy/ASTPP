<?php extend('master.php') ?>

<?php startblock('extra_head') ?>
<script type="text/javascript">
    $(document).ready(function(event){
        var productUrl = "<?php echo base_url() . "products" ?>";
        build_grid("flex1", productUrl, <?php echo $grid_fields ?> , <?php echo $grid_buttons ?>);
        $('.checkall').click(function () {
            $('.chkRefNos').attr('checked', this.checked); //if you want to select/deselect checkboxes use this
        });
        $("#account_search_btn").click(function(){
            post_request_for_search("flex1", productUrl, "products_search");
        });        
        $("#id_reset").click(function(){ 
            clear_search_request("flex1", productUrl);
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
<?php echo "Products" ?>
<?php endblock() ?>

<?php startblock('content') ?>

<section class="slice color-three padding-b-20">
    <div class="w-section inverse no-padding">
        <div class="container">
            <div class="row">
                <div class="col-md-12">      
                    <form method="POST" action="del/0/" enctype="multipart/form-data" id="ListForm">
                        <table id="flex1" align="left" style="display:none;"></table>
                    </form>
                </div>  
            </div>
        </div>
    </div>
</section>

<?php endblock() ?>

<?php end_extend() ?>