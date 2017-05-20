<? extend('custom_master.php') ?>
<? startblock('page-title') ?>
<?= $page_title ?>
<? endblock() ?>

<? startblock('content') ?>
<section class="slice color-three padding-b-20">
    <div class="w-section inverse no-padding">
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    <p>
                    <pre>
<span style="font-size: larger">Example define of table with cycle by variable:</span>
&lt;table&gt;
    &lt;thead&gt;
        &lt;tr&gt;&lt;td&gt;id&lt;/td&gt;&lt;td&gt;Name&lt;/td&gt;&lt;/tr&gt;
    &lt;/thead>
    &lt;tbody <b>foreach="$packages"</b>&gt;
        &lt;tr>&lt;td&gt;<b>{$packages[{$i}].id}</b>&lt;/td&gt;&lt;td&gt;<b>{$packages[{$i}].package_name}</b>&lt;/td&gt;&lt;/tr&gt;
    &lt;/tbody&gt;
&lt;/table&gt;
                    </pre>
                    </p>
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <td width="20%">Name</td>
                                <td width="50%">Query</td>
                                <td width="30%">Comment</td>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($variables as $variable) { ?>
                            <tr>
                                <td><?= $variable['name'] ?></td>
                                <td><?= $variable['query'] ?></td>
                                <td><?= $variable['comment'] ?></td>
                            </tr>
                        <?php }?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>
<? endblock() ?>
<? end_extend() ?>

