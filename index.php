<?php
/**
 * @Created by          : Drajat Hasan
 * @Date                : 2021-03-30 16:18:40
 * @File name           : index.php
 */

defined('INDEX_AUTH') OR die('Direct access not allowed!');

// IP based access limitation
require LIB . 'ip_based_access.inc.php';
do_checkIP('smc');
do_checkIP('smc-bibliography');
// start the session
require SB . 'admin/default/session.inc.php';
// set dependency
require SIMBIO . 'simbio_GUI/table/simbio_table.inc.php';
require SIMBIO . 'simbio_GUI/form_maker/simbio_form_table_AJAX.inc.php';
require SIMBIO . 'simbio_GUI/paging/simbio_paging.inc.php';
require SIMBIO . 'simbio_DB/datagrid/simbio_dbgrid.inc.php';
// end dependency

// privileges checking
$can_read = utility::havePrivilege('bibliography', 'r');

if (!$can_read) {
    die('<div class="errorBox">' . __('You are not authorized to view this section') . '</div>');
}

function httpQuery($query = [])
{
    return http_build_query(array_unique(array_merge($_GET, $query)));
}

$page_title = 'Item Copy Generator';

/* Action Area */
if (isset($_POST['saveData']))
{
    if (!isset($_POST['overrideAll']))
    {
        utility::jsAlert('Tidak ada yang dipeperbaharui, karena opsi perbaharui tidak dicentang!');
        exit;
    }

    // update call number
    $update = $dbs->query('update item set call_number = "'.$dbs->escape_string($_POST['call_number']).'" where biblio_id = '.(int)$_POST['updateRecordID']);

    if ($update)
    {
        utility::jsAlert('Berhasil memperbaharui no panggil.');
        echo '<script>parent.location.reload()</script>';
    }
    else
    {
        utility::jsAlert('Gagal memperbaharui no panggil!');
    }
    exit;
}

if (isset($_POST['setupCopy']))
{
    $dataQuery = $dbs->query('select item_code, call_number from item where biblio_id= '.(int)$_POST['updateRecordID'].' order by input_date asc');

    if ($dataQuery->num_rows > 0)
    {
        $no = 1;
        while ($item = $dataQuery->fetch_row())
        {
            $itemCode = $dbs->escape_string($item[0]);
            @$dbs->query('update item set call_number = "'.$item[1].' c.'.$no.'" where item_code = "'.$itemCode.'"');
            $no++;
        }
    }
}

/* End Action Area */
if (isset($_GET['genNumber']) && isset($_GET['biblio_id']))
{
    ob_start();
    // try query
    $itemID = (integer)isset($_GET['biblio_id']) ? $_GET['biblio_id'] : 0;
    $_sql_rec_q = sprintf('select title, call_number from biblio where biblio_id=%d', $itemID);
    $rec_q = $dbs->query($_sql_rec_q);
    $rec_d = $rec_q->fetch_assoc();

    // create new instance
    $form = new simbio_form_table_AJAX('mainForm', $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING'], 'post');
    $form->submit_button_attr = 'name="saveData" value="' . __('Update') . '" class="s-btn btn btn-default"';
    // form table attributes
    $form->table_attr = 'id="dataList" cellpadding="0" cellspacing="0"';
    $form->table_header_attr = 'class="alterCell"';
    $form->table_content_attr = 'class="alterCell2"';

    // set form
    $form->addHidden('updateRecordID', $itemID);
    /* Title */
    $form->addAnything(__('Title'), $rec_d['title']);

    /* Call Number */
    $anything  = '<input type="text" class="form-control" title="'.__('Call Number').'" name="call_number" value="'.$rec_d['call_number'].'"/>';
    $anything .= '<input type="checkbox" name="overrideAll"/> Perbaharui Semua<br>';
    $anything .= '<small style="font-style: italic; font-weight: bold">NB : mengubah Nomor Panggil akan merubah seluruh Nomor Panggil pada data eksemplar</small>';
    $form->addAnything(__('Call Number'), $anything);

    // List of item';
    $anything = '<iframe name="authorIframe" id="authorIframe" class="form-control" style="width: 100%; height: 200px;" src="' . str_replace('&genNumber=yes', '', $_SERVER['PHP_SELF'] . '?' . httpQuery(['listItem' => 'ok'])) . '"></iframe>';
    $form->addAnything(__('Items'), $anything);

    // print out the form object
    echo $form->printOut();
    // Set clearn
    $content = ob_get_clean();
    // page title
    $page_title = 'Item Copy Generator';
    // include the page template
    require SB.'/admin/'.$sysconf['admin_template']['dir'].'/notemplate_page_tpl.php';
    exit;
}
else if (isset($_GET['listItem']) && isset($_GET['biblio_id']))
{
    ob_start();
    // try query
    $itemID = (integer)isset($_GET['biblio_id']) ? $_GET['biblio_id'] : 0;
    $_sql_rec_q = sprintf('select item_code, call_number from item where biblio_id=%d', $itemID);
    $rec_q = $dbs->query($_sql_rec_q);
    
    $html  = '<form method="POST" action="'.$_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING'].'">
                <input type="hidden" name="updateRecordID" value="'.$itemID.'"/>
                <button class="btn btn-info mb-3" name="setupCopy">Buat Kode Salin</button>
              </form>';
    $html .= '<ol style="list-style-type: none" class="p-0">';
    while ($rec_d = $rec_q->fetch_assoc())
    {
        $callNumber = (!empty($rec_d['call_number'])) ? $rec_d['call_number'] : 'Tidak ada no panggil';
        $html .= '<li>' . $rec_d['item_code'] . ' - ' . $callNumber . '</li>';
    }
    $html .= '</ol>';

    echo $html;
    // Set clearn
    $content = ob_get_clean();
    // page title
    $page_title = 'List Item';
    // include the page template
    require SB.'/admin/'.$sysconf['admin_template']['dir'].'/notemplate_page_tpl.php';
    exit;
}
else
{
?>
    <div class="menuBox">
        <div class="menuBoxInner memberIcon">
            <div class="per_title">
                <h2><?php echo $page_title; ?></h2>
            </div>
            <div class="sub_section">
                <div class="btn-group">
                    <a href="<?= $_SERVER['PHP_SELF'] . '?' . httpQuery() ?>" class="btn btn-default">Daftar</a>
                </div>
                <form name="search" action="<?= $_SERVER['PHP_SELF'] . '?' . httpQuery() ?>" id="search" method="get" class="form-inline"><?php echo __('Search'); ?>
                    <input type="text" name="keywords" class="form-control col-md-3"/>
                    <input type="submit" id="doSearch" value="<?php echo __('Search'); ?>"
                            class="s-btn btn btn-default"/>
                </form>
            </div>
        </div>
    </div>
<?php

    /* Datagrid area */
    /**
     * table spec
     * ---------------------------------------
     * Tuliskan nama tabel pada variabel $table_spec. Apabila anda 
     * ingin melakukan pegabungan banyak tabel, maka anda cukup menulis kan
     * nya saja layak nya membuat query seperti biasa
     *
     * Contoh :
     * - dummy_plugin as dp left join non_dummy_plugin as ndp on dp.id = ndp.id ... dst
     *
     */
    $table_spec = 'biblio as b inner join item as i on i.biblio_id = b.biblio_id';

    // membuat datagrid
    $datagrid = new simbio_datagrid();

    /** 
     * Menyiapkan kolom
     * -----------------------------------------
     * Format penulisan sama seperti anda menuliskan di query pada phpmyadmin/adminer/yang lain,
     * hanya di SLiMS anda diberikan beberapa opsi seperti, penulisan dengan gaya multi parameter,
     * dan gaya single parameter.
     *
     * Contoh :
     * - Single Parameter : $datagrid->setSQLColumn('id', 'kolom1, kolom2, kolom3'); // penulisan langsung
     * - Single Parameter : $datagrid->setSQLColumn('id', 'kolom1', 'kolom2', 'kolom3'); // penulisan secara terpisah
     *
     * Catatan :
     * - Jangan lupa menyertakan kolom yang bersifat PK (Primary Key) / FK (Foreign Key) pada urutan pertama,
     *   karena kolom tersebut digunakan untuk pengait pada proses lain.
     */
    $datagrid->setSQLColumn('b.title as "'.__('Title').'", count(i.item_code) as "Jml Eksemplar", b.biblio_id as "Aksi", b.last_update as "'.__('Last Update').'"');

    /**
     * Order by
     */
    $datagrid->sql_group_by = 'b.title';

    /** 
     * Pencarian data
     * ------------------------------------------
     * Bagian ini tidak lepas dari nama kolom dari tabel yang digunakan.
     * Jadi, untuk pencarian yang lebih banyak anda dapat menambahkan kolom pada variabel
     * $criteria
     *
     * Contoh :
     * - $criteria = ' kolom1 = "'.$keywords.'" OR kolom2 = "'.$keywords.'" OR kolom3 = "'.$keywords.'"';
     * - atau anda bisa menggunakan query anda.
     */
    if (isset($_GET['keywords']) AND $_GET['keywords']) 
    {
        $keywords = utility::filterData('keywords', 'get', true, true, true);
        $criteria = ' b.title LIKE "%'.$keywords.'%"';
        // jika ada keywords maka akan disiapkan criteria nya
        $datagrid->setSQLCriteria($criteria);
    }

    /**
     * Modify Column
     */
    function setConfigButton($db, $data)
    {
        return '<a href="' . $_SERVER['PHP_SELF'] . '?' . httpQuery(['genNumber' => 'yes', 'biblio_id' => $data[2]]) . '" class="notAJAX openPopUp btn btn-primary generateItem" title="Buat Salinan Item">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-gear-fill" viewBox="0 0 16 16">
            <path d="M9.405 1.05c-.413-1.4-2.397-1.4-2.81 0l-.1.34a1.464 1.464 0 0 1-2.105.872l-.31-.17c-1.283-.698-2.686.705-1.987 1.987l.169.311c.446.82.023 1.841-.872 2.105l-.34.1c-1.4.413-1.4 2.397 0 2.81l.34.1a1.464 1.464 0 0 1 .872 2.105l-.17.31c-.698 1.283.705 2.686 1.987 1.987l.311-.169a1.464 1.464 0 0 1 2.105.872l.1.34c.413 1.4 2.397 1.4 2.81 0l.1-.34a1.464 1.464 0 0 1 2.105-.872l.31.17c1.283.698 2.686-.705 1.987-1.987l-.169-.311a1.464 1.464 0 0 1 .872-2.105l.34-.1c1.4-.413 1.4-2.397 0-2.81l-.34-.1a1.464 1.464 0 0 1-.872-2.105l.17-.31c.698-1.283-.705-2.686-1.987-1.987l-.311.169a1.464 1.464 0 0 1-2.105-.872l-.1-.34zM8 10.93a2.929 2.929 0 1 1 0-5.86 2.929 2.929 0 0 1 0 5.858z"/>
        </svg>
        </a>';
    }
    $datagrid->modifyColumnContent(2, 'callback{setConfigButton}');

    /** 
     * Atribut tambahan
     * --------------------------------------------
     * Pada bagian ini anda dapat menentukan atribut yang akan muncul pada datagrid
     * seperti judul tombol, dll
     */
    // set table and table header attributes
    $datagrid->icon_edit = SWB.'admin/'.$sysconf['admin_template']['dir'].'/'.$sysconf['admin_template']['theme'].'/edit.gif';
    $datagrid->table_name = 'memberList';
    $datagrid->table_attr = 'id="dataList" class="s-table table"';
    $datagrid->table_header_attr = 'class="dataListHeader" style="font-weight: bold;"';
    // set delete proccess URL
    $datagrid->chbox_form_URL = $_SERVER['PHP_SELF'];

    // put the result into variables
    $datagrid_result = $datagrid->createDataGrid($dbs, $table_spec, 20, false); // object database, spesifikasi table, jumlah data yang muncul, boolean penentuan apakah data tersebut dapat di edit atau tidak.
    if (isset($_GET['keywords']) AND $_GET['keywords']) {
        $msg = str_replace('{result->num_rows}', $datagrid->num_rows, __('Found <strong>{result->num_rows}</strong> from your keywords'));
        echo '<div class="infoBox">' . $msg . ' : "' . htmlspecialchars($_GET['keywords']) . '"<div>' . __('Query took') . ' <b>' . $datagrid->query_time . '</b> ' . __('second(s) to complete') . '</div></div>';
    }
    // menampilkan datagrid
    echo $datagrid_result;
    /* End datagrid */
    ?>
    <script>
        
            let h = Number(window.innerHeight) - 100;
            let w = Number(window.innerWidth) - 80;
            let selector = document.querySelectorAll('.generateItem');

            // attribute
            function attr(selector, arrayAttribute)
            {
                if (typeof arrayAttribute === 'object')
                {
                    arrayAttribute.forEach((Attribute) => {
                        selector.forEach((dom) => {
                            dom.setAttribute(Attribute[0], Attribute[1])
                        })
                    })
                }
            }
            
            attr(selector, [['width', w],['height', h]]);
        
    </script>
<?php
}
?>
<iframe name="submitExec" style="display:none;"></iframe>
