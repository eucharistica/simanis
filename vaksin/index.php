<?php
require_once('../config.php');

// 1) Tentukan rentang tanggal yang aktif (dipakai untuk semua aksi)
if (isset($_POST['tanggal_cari'], $_POST['tanggal_cari2'])) {
    $tanggal_cari  = $_POST['tanggal_cari'];
    $tanggal_cari2 = $_POST['tanggal_cari2'];
} else {
    // Default: hari ini
    $tanggal_cari  = date('Y-m-d');
    $tanggal_cari2 = date('Y-m-d');
}

// di atas: baca nilai filter status
$status_filter = isset($_POST['status_filter']) ? $_POST['status_filter'] : 'all';

// 2) Handler tandai sudah input ke Kemenkes
if (isset($_POST['mark_kemkes'], $_POST['kemkes_id'])) {
    $kemkesId = (int) $_POST['kemkes_id'];

    query("
        UPDATE vaksin_internasional
        SET kemkes_done = 1,
            kemkes_done_at = NOW()
        WHERE id = '$kemkesId'
        LIMIT 1
    ");
}

$API_BASE = "http://100.10.1.4/api-website/";
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>SIMANIS</title>

  <!-- Google Font: Source Sans Pro -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="../plugins/fontawesome-free/css/all.min.css">
  <!-- DataTables -->
  <link rel="stylesheet" href="../plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
  <link rel="stylesheet" href="../plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
  <link rel="stylesheet" href="../plugins/datatables-buttons/css/buttons.bootstrap4.min.css">
  <!-- daterange picker -->
  <link rel="stylesheet" href="../plugins/daterangepicker/daterangepicker.css">
  <!-- iCheck for checkboxes and radio inputs -->
  <link rel="stylesheet" href="../plugins/icheck-bootstrap/icheck-bootstrap.min.css">
  <!-- Bootstrap Color Picker -->
  <link rel="stylesheet" href="../plugins/bootstrap-colorpicker/css/bootstrap-colorpicker.min.css">
  <!-- Tempusdominus Bootstrap 4 -->
  <link rel="stylesheet" href="../plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css">
  <!-- Select2 -->
  <link rel="stylesheet" href="../plugins/select2/css/select2.min.css">
  <link rel="stylesheet" href="../plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css">
  <!-- Bootstrap4 Duallistbox -->
  <link rel="stylesheet" href="../plugins/bootstrap4-duallistbox/bootstrap-duallistbox.min.css">
  <!-- BS Stepper -->
  <link rel="stylesheet" href="../plugins/bs-stepper/css/bs-stepper.min.css">
  <!-- dropzonejs -->
  <link rel="stylesheet" href="../plugins/dropzone/min/dropzone.min.css">
  <!-- Theme style -->
  <link rel="stylesheet" href="../dist/css/adminlte.min.css">
  <!-- Favicon -->
  <link rel="icon" type="image/x-icon" href="../favicon.ico"> 
  
    <style>
      td.copy-cell {
        cursor: pointer;
        position: relative;
      }
    
      /* Animasi tulisan Copied */
      .copy-badge {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background: #28a745;
        color: white;
        font-size: 11px;
        padding: 3px 6px;
        border-radius: 4px;
        opacity: 0;
        pointer-events: none;
        z-index: 999;
        transition: opacity 0.2s ease-in-out;
      }
    </style>
    <style>
      tr.kemkes-done {
        background-color: #e6ffed !important;
      }
      
    @media print {
        @page {
            size: landscape;
            margin: 10mm;
        }
    }
    </style>
</head>

<body class="hold-transition sidebar-mini">
  
  <!-- Content Header (Page header) -->
  <section class="content-header">
    <div class="col-sm-12" style="text-align: center">
      <h1>LAPORAN KUNJUNGAN VAKSIN INTERNASIONAL RSUD MATRAMAN</h1>
    </div>
    <div class="container-fluid">
      <div class="row mb-3">
        <div class="col-sm-12">
          <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="">Home</a></li>
            <li class="breadcrumb-item active">Data Laporan</li>
          </ol>
        </div>
      </div>
    </div><!-- /.container-fluid -->
  </section>

  <!-- Main content -->
  <section class="content">
    <div class="container-fluid">
      <div class="row">
        <div class="col-12">
          <form action="" method="post">
            <div class="form-group">
              <div class="row">
                <div class="col-xs-12 col-md-2">
                  <div class="form-group">
                    <label for="datepickerFrom1">Dari</label>
                    <div class="input-group">
                      <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fa fa-calendar"></i></span>
                      </div>
                      <input type="date" class="form-control" id="datepickerFrom1"
                          name="tanggal_cari" value="<?php echo htmlspecialchars($tanggal_cari); ?>">
                    </div>
                  </div>
                </div>
                <div class="col-xs-12 col-md-2">
                  <div class="form-group">
                    <label for="datepickerTO1">Sampai</label>
                    <div class="input-group">
                      <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fa fa-calendar"></i></span>
                      </div>
                      <input type="date" class="form-control" id="datepickerTO1"
                          name="tanggal_cari2" value="<?php echo htmlspecialchars($tanggal_cari2); ?>">
                    </div>
                  </div>
                </div>
                <div class="col-xs-12 col-md-3">
                  <div class="form-group">
                    <label>Status Kemenkes</label>
                    <select name="status_filter" class="form-control">
                      <option value="all"   <?php echo $status_filter=='all'?'selected':''; ?>>Semua</option>
                      <option value="done"  <?php echo $status_filter=='done'?'selected':''; ?>>Sudah input</option>
                      <option value="not"   <?php echo $status_filter=='not'?'selected':''; ?>>Belum input</option>
                    </select>
                  </div>
                </div>
                <div class="col-xs-12 col-md-3">
                  <div class="form-group">
                    <label>&nbsp;</label>
                    <div class="input-group">
                      <button type="submit" name="search" class="btn btn-info btn-block">
                        <i class="fa fa-search"></i> Search
                      </button>
                    </div>
                  </div>
                </div>                                                
              </div>
            </div>
          </form>

          <div class="card card-primary card-tabs">
            <div class="card-header p-0 pt-1">
              <ul class="nav nav-tabs" id="custom-tabs-one-tab" role="tablist">
                <li class="nav-item">
                  <a class="nav-link active" id="custom-tabs-one-home-tab" data-toggle="pill" href="#custom-tabs-one-home" role="tab" aria-controls="custom-tabs-one-home" aria-selected="true">Data Vaksinasi</a>
                </li>
              </ul>
            </div>
            <div class="card-body">
              <div class="tab-content" id="custom-tabs-one-tabContent">
                <div class="tab-pane fade show active" id="custom-tabs-one-home" role="tabpanel" aria-labelledby="custom-tabs-one-home-tab">
                  <!-- /.card-header -->
                  <div class="card-body">
                    <table id="example2" class="table table-bordered table-striped">
                      <thead>
                        <tr>
                          <th>No</th>
                          <th>Tanggal Vaksin</th>
                          <th>Nama</th>
                          <th>JK</th>
                          <th>Email</th>
                          <th>Tanggal Lahir</th>
                          <th>No KTP</th>
                          <th>No EICV</th>
                          <th>No Paspor</th>
                          <th>Jenis Vaksin</th>
                          <th>Alamat</th>
                          <th>No Telp</th>
                          <th>Keperluan</th>
                          <th>Paspor</th>
                          <th>Status</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php
                        $no = 0;
                        $tgl_cari = FormatTgl("Y-m-d", $tanggal_cari);
                        $tgl_cari2 = FormatTgl("Y-m-d", $tanggal_cari2);
                        
                        $whereStatus = '';
                        if ($status_filter === 'done') {
                            $whereStatus = " AND v.kemkes_done = 1 ";
                        } elseif ($status_filter === 'not') {
                            $whereStatus = " AND (v.kemkes_done = 0 OR v.kemkes_done IS NULL) ";
                        }
                        
                        $sql_emr = query("SELECT
                            v.*,
                            p.alamat 
                        FROM
                            vaksin_internasional v
                            JOIN pasien p ON p.no_rkm_medis = v.no_rkm_medis 
                        WHERE v.id!='0'
                          AND v.dov BETWEEN '$tgl_cari' AND '$tgl_cari2'
                          $whereStatus
                        ORDER BY v.created_at");

                        while ($row = fetch_array($sql_emr)) {
                          $no++;
                          if ($row['jk']=='L'){
                              $row['jk']='Male';
                          }
                          else {
                              $row['jk']='Female';
                          }
                        ?>
                          <tr class="<?php echo !empty($row['kemkes_done']) ? 'kemkes-done' : ''; ?>">
                              <td><?php echo $no; ?></td>
                            
                              <td class="copy-cell" data-label="Tanggal Vaksin"><?php echo $row['dov']; ?></td>
                              <td class="copy-cell" data-label="Nama"><?php echo $row['fname']; ?></td>
                              <td class="copy-cell" data-label="JK"><?php echo $row['jk']; ?></td>
                              <td class="copy-cell" data-label="Email"><?php echo $row['email']; ?></td>
                              <td class="copy-cell" data-label="Tanggal Lahir">
                                <?php echo date('d-m-Y', strtotime($row['tgl_lahir'])); ?>
                              </td>
                              <td class="copy-cell" data-label="No KTP"><?php echo $row['no_ktp']; ?></td>
                              <td class="copy-cell" data-label="No EICV"><?php echo $row['no_eicv']; ?></td>
                              <td class="copy-cell" data-label="No Paspor"><?php echo $row['no_paspor']; ?></td>
                              <td class="copy-cell" data-label="Jenis Vaksin"><?php echo $row['vaksin_nama']; ?></td>
                              <td class="copy-cell" data-label="Alamat"><?php echo $row['alamat']; ?></td>
                              <td class="copy-cell" data-label="No Telp"><?php echo $row['no_wa']; ?></td>
                              <td class="copy-cell" data-label="Keperluan"><?php echo $row['keperluan']; ?></td>
                              <td style="text-align:center;">
                                  <a href="https://rsudmatraman.my.id/api-website/<?php echo $row['passport_file']; ?>"
                                     class="btn btn-sm btn-primary"
                                     target="_blank"
                                     rel="noopener noreferrer">
                                    <i class="fas fa-eye"></i> Lihat
                                  </a>
                              </td>
                              <td class="no-copy">
                                <form method="post" style="display:inline;">
                                  <!-- bawa filter tanggal supaya tidak hilang -->
                                  <input type="hidden" name="tanggal_cari" value="<?php echo htmlspecialchars($tanggal_cari); ?>">
                                  <input type="hidden" name="tanggal_cari2" value="<?php echo htmlspecialchars($tanggal_cari2); ?>">
                                  <input type="hidden" name="status_filter" value="<?php echo htmlspecialchars($status_filter); ?>">
                                  <input type="hidden" name="kemkes_id"     value="<?php echo (int) $row['id']; ?>">
                                  <button
                                    type="submit"
                                    name="mark_kemkes"
                                    class="btn btn-xs <?php echo !empty($row['kemkes_done']) ? 'btn-success' : 'btn-outline-success'; ?>"
                                    <?php echo !empty($row['kemkes_done']) ? 'disabled' : ''; ?>
                                    title="<?php echo !empty($row['kemkes_done']) ? 'Sudah ditandai' : 'Tandai sudah input ke Kemenkes'; ?>"
                                  >
                                    <i class="fas fa-clipboard-check"></i>
                                  </button>
                                </form>  
                                <?php if (!empty($row['kemkes_done'])): ?>
                                  <span class="badge badge-success">
                                    <i class="fas fa-check"></i> Sudah input
                                  </span><br>
                                  <small><?php echo htmlspecialchars($row['kemkes_done_at']); ?></small>
                                <?php else: ?>
                                  <span class="badge badge-secondary">Belum</span>
                                <?php endif; ?>
                              </td>
                          </tr>
                        <?php } ?>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- jQuery dan scripts lainnya tetap sama -->
  <!-- jQuery -->
  <script src="../plugins/jquery/jquery.min.js"></script>
  <!-- Bootstrap 4 -->
  <script src="../plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
  <!-- DataTables & Plugins -->
  <script src="../plugins/datatables/jquery.dataTables.min.js"></script>
  <script src="../plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
  <script src="../plugins/datatables-responsive/js/dataTables.responsive.min.js"></script>
  <script src="../plugins/datatables-responsive/js/responsive.bootstrap4.min.js"></script>
  <script src="../plugins/datatables-buttons/js/dataTables.buttons.min.js"></script>
  <script src="../plugins/datatables-buttons/js/buttons.bootstrap4.min.js"></script>
  <script src="../plugins/jszip/jszip.min.js"></script>
  <script src="../plugins/pdfmake/pdfmake.min.js"></script>
  <script src="../plugins/pdfmake/vfs_fonts.js"></script>
  <script src="../plugins/datatables-buttons/js/buttons.html5.min.js"></script>
  <script src="../plugins/datatables-buttons/js/buttons.print.min.js"></script>
  <script src="../plugins/datatables-buttons/js/buttons.colVis.min.js"></script>
  <!-- AdminLTE App -->
  <script src="../dist/js/adminlte.min.js"></script>
  
  <!-- Page specific script -->
  <script>
  $(function () {
    // Inisialisasi DataTable SEKALI saja
    // var table = $('#example2').DataTable({
    //   paging: true,
    //   lengthChange: true,
    //   autoWidth: false,
    //   responsive: true,
    //   buttons: ["copy", "csv", "excel", "pdf", "print", "colvis"]
    // });

    var table = $('#example2').DataTable({
        paging: true,
        lengthChange: true,
        autoWidth: false,
        responsive: true,
        buttons: [
            {
                extend: 'excelHtml5',
                text: 'Export Excel',
                exportOptions: {
                    columns: [0,1,2,3,4,5,6,7,8,9,10,11,12],
                    format: {
                        body: function (data, row, column) {
    
                            // Kolom No KTP / Paspor: paksa jadi text
                            if (column === 6) {
                                return "'" + data;  // <-- trik excel treat as text
                            }
    
                            return data;
                        }
                    }
                }
            },
            {
                extend: 'print',
                text: 'Print',
                exportOptions: {
                    columns: [0,1,2,3,4,5,6,7,8,9,10,11,12]
                }
            }, "colvis"
        ]
    }).buttons().container().appendTo('#example2_wrapper .col-md-6:eq(0)');

    // Klik sel dengan class copy-cell => copy isi sel
    $('#example2 tbody').on('click', 'td.copy-cell', function () {

      var text = $(this).text().trim();
      if (!text) return;
    
      navigator.clipboard.writeText(text).then(() => {
    
        var $cell = $(this);
    
        // Efek highlight hijau
        $cell.addClass('copied');
        setTimeout(() => { 
          $cell.removeClass('copied'); 
        }, 600);
    
        // Tambah badge Copied!
        let badge = $('<span class="copy-badge">Copied!</span>');
        $cell.append(badge);
    
        setTimeout(() => {
          badge.css('opacity', '1');
        }, 10);
    
        setTimeout(() => {
          badge.css('opacity', '0');
          setTimeout(() => badge.remove(), 200);
        }, 700);
    
      }).catch(err => {
        console.error('Clipboard failed:', err);
        alert('Gagal menyalin ke clipboard.');
      });
    });

  });
  </script>


</body>
</html>
