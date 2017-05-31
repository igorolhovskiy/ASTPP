<? extend('master.php') ?>
<?php error_reporting(E_ERROR); ?>
<? startblock('extra_head') ?>

<script type="text/javascript" src="<?php echo base_url(); ?>assets/tinymce/tinymce.min.js">

</script>
<script type="text/javascript">

tinymce.init({
  selector: 'textarea',
  height: 200,
  width: 'auto',
  theme: 'modern',
  plugins: [
    'advlist autolink lists link image charmap print preview hr anchor pagebreak',
    'searchreplace wordcount visualblocks visualchars code fullscreen',
    'insertdatetime media nonbreaking save table contextmenu directionality',
    'emoticons template paste textcolor colorpicker textpattern imagetools'
  ],
  toolbar1: 'insertfile undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image | print preview media | forecolor backcolor emoticons',
  image_advtab: true,
  templates: [
    { title: 'Test template 1', content: 'Test 1' },
    { title: 'Test template 2', content: 'Test 2' }
  ],
  content_css: [
    '<?php echo base_url(); ?>assets/css/tinymce_fast_font.css',
    '<?php echo base_url(); ?>assets/css/tinymce_codepen_min.css'
  ],
  extended_valid_elements : 'tbody[foreach|class|width|height]'
 });

function preview_pdf() {
  var template = {
    'head_template': tinyMCE.get('head_template').getContent({format: 'raw'}),
    'page1_template': tinyMCE.get('page1_template').getContent({format: 'raw'}),
    'page2_template': tinyMCE.get('page2_template').getContent({format: 'raw'}),
    'footer_template': tinyMCE.get('footer_template').getContent({format: 'raw'})
  };
  $.ajax({
    type: "POST",
    url: "<?= base_url()?>invoices/templates/preview_pdf",
    data: template,
    dataType: 'json',
    success: function (response) {
      if (response && response.success) {
        window.open("data:application/pdf;base64, " + response.contentPdf);
      } else {
        alert('Failed forming pdf. ' + response.message);
      }
    }
  });
}
</script>

<?php endblock() ?>
<?php startblock('page-title') ?>
<?=$page_title?>
<?php endblock() ?>
<?php startblock('content')?>

<div>
  <div>
    <section class="slice color-three no-margin">
	<div class="w-section inverse no-padding">
        <button name="action" type="button" value="preview" class="btn btn-line-sky pull-left" onclick="preview_pdf()">Preview PDF</button>
        <a href="<?php echo base_url();?>invoices/templates/help" target="_blank" role="button" class="pull-right" style="min-width:0px;"><i class="fa fa-info-circle fa-2x  margin-r-10 margin-t-51" aria-hidden="true"></i></a>
            <?php echo $form; ?>
			<?php
				if (isset($validation_errors) && $validation_errors != '') { ?>
				<script>
					var ERR_STR = '<?php echo $validation_errors; ?>';
					print_error(ERR_STR);
				</script>
			<? } ?>           
        </div>      
    </section>
  </div>
</div>

<? endblock() ?>
<? startblock('sidebar') ?>
<? endblock() ?>
<? end_extend() ?>
