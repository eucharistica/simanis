<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';
?>
<style>
.pendapatan-rajal-page .filter-card label { font-weight:600; font-size:.85rem; margin-bottom:.35rem; }
.pendapatan-rajal-page .filter-card .form-group { margin-bottom:.75rem; }
.pendapatan-rajal-page table.dataTable thead th { vertical-align:middle; white-space:nowrap; }
.pendapatan-rajal-page .report-context { font-size:.85rem; }
</style>

<div class="container-fluid px-2 px-md-3 py-3 pendapatan-rajal-page">
  <div class="mb-3">
    <h3 class="mb-1 font-weight-bold">Laporan Pendapatan Rawat Jalan</h3>
    <div class="text-muted">Pendapatan rawat jalan per bulan tanpa potongan biaya</div>
  </div>

  <div id="alertContainer"></div>

  <div class="card card-soft filter-card mb-3">
    <div class="card-header card-soft-header py-2">
      <div class="card-section-title">Filter Data</div>
    </div>
    <div class="card-body">
      <div class="form-row align-items-end">
        <div class="form-group col-md-2">
          <label for="tgl1">Tanggal Mulai</label>
          <input type="date" class="form-control form-control-sm" id="tgl1" value="<?= date('Y-m-01') ?>">
        </div>
        <div class="form-group col-md-2">
          <label for="tgl2">Tanggal Akhir</label>
          <input type="date" class="form-control form-control-sm" id="tgl2" value="<?= date('Y-m-d') ?>">
        </div>
        <div class="form-group col-md-4">
          <label for="kdPoli">Poli</label>
          <select id="kdPoli" class="form-control form-control-sm">
            <option value="">Semua Poli</option>
          </select>
        </div>
        <div class="form-group col-md-4">
          <label for="kdDokter">Dokter</label>
          <select id="kdDokter" class="form-control form-control-sm">
            <option value="">Semua Dokter</option>
          </select>
        </div>
      </div>
    </div>
    <div class="card-footer bg-white py-2">
      <div class="d-flex" style="gap:8px">
        <button type="button" class="btn btn-sm btn-primary" id="btnCari">
          <i class="fas fa-search mr-1"></i>Tampilkan
        </button>
        <button type="button" class="btn btn-sm btn-secondary" id="btnReset">Reset</button>
      </div>
    </div>
  </div>

  <div class="card card-soft">
    <div class="card-header card-soft-header-primary py-2 d-flex flex-wrap justify-content-between align-items-center">
      <div class="card-section-title">Data Pendapatan per Bulan</div>
      <div class="text-muted report-context" id="periodePendapatan">-</div>
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table id="dtPendapatanRajal" class="table table-striped table-bordered" style="width:100%">
          <thead>
            <tr>
              <th style="width:70px">No.</th>
              <th>Bulan</th>
              <th>Total Pendapatan</th>
            </tr>
          </thead>
          <tfoot>
            <tr>
              <th colspan="2" class="text-right">Total :</th>
              <th id="grandTotal" class="text-right">Rp 0</th>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
const API = {
  poli: 'api/poli-list.php',
  dokter: 'api/pendapatan-rajal-dokter-list.php',
  total: 'api/pendapatan-rajal-total.php'
};

const rupiah = new Intl.NumberFormat('id-ID', {
  style: 'currency', currency: 'IDR', minimumFractionDigits: 0, maximumFractionDigits: 0
});
const tanggalId = new Intl.DateTimeFormat('id-ID', { day:'2-digit', month:'long', year:'numeric' });
let table = null;

function showAlert(message){
  document.getElementById('alertContainer').innerHTML =
    `<div class="alert alert-danger py-2">${message}</div>`;
}

function filters(){
  const tgl1 = document.getElementById('tgl1').value;
  const tgl2 = document.getElementById('tgl2').value;
  return {
    tgl1,
    tgl2,
    kd_poli: document.getElementById('kdPoli').value,
    kd_dokter: document.getElementById('kdDokter').value
  };
}

function setOptions(select, rows, valueKey, labelKey, defaultLabel){
  const selected = select.value;
  select.innerHTML = `<option value="">${defaultLabel}</option>`;
  rows.forEach(row => {
    const option = document.createElement('option');
    option.value = row[valueKey];
    option.textContent = row[labelKey];
    select.appendChild(option);
  });
  if ([...select.options].some(option => option.value === selected)) select.value = selected;
}

async function loadPoli(){
  const response = await fetch(API.poli, { headers:{Accept:'application/json'} });
  if (!response.ok) throw new Error('Daftar poli tidak dapat dimuat.');
  const json = await response.json();
  setOptions(document.getElementById('kdPoli'), json.data || [], 'kd_poli', 'nm_poli', 'Semua Poli');
}

async function loadDokter(){
  const f = filters();
  const params = new URLSearchParams({tgl1:f.tgl1, tgl2:f.tgl2, kd_poli:f.kd_poli});
  const response = await fetch(`${API.dokter}?${params}`, { headers:{Accept:'application/json'} });
  if (!response.ok) throw new Error('Daftar dokter tidak dapat dimuat.');
  const json = await response.json();
  setOptions(document.getElementById('kdDokter'), json.data || [], 'kd_dokter', 'nm_dokter', 'Semua Dokter');
}

async function loadTotal(){
  const f = filters();
  if (!f.tgl1 || !f.tgl2) return showAlert('Tanggal mulai dan tanggal akhir wajib diisi.');
  if (f.tgl1 > f.tgl2) return showAlert('Tanggal mulai tidak boleh melebihi tanggal akhir.');

  const button = document.getElementById('btnCari');
  button.disabled = true;
  document.getElementById('alertContainer').innerHTML = '';

  try {
    const body = new URLSearchParams(f);
    const response = await fetch(API.total, {
      method:'POST', body, headers:{Accept:'application/json', 'Content-Type':'application/x-www-form-urlencoded'}
    });
    const json = await response.json();
    if (!response.ok || !json.ok) throw new Error(json.message || 'Pendapatan tidak dapat dimuat.');

    table.clear();
    table.rows.add(json.data || []).draw();
    document.getElementById('grandTotal').textContent = rupiah.format(Number(json.total || 0));
    const awal = tanggalId.format(new Date(`${json.filters.tgl1}T00:00:00`));
    const akhir = tanggalId.format(new Date(`${json.filters.tgl2}T00:00:00`));
    const cakupan = [json.filters.nm_poli, json.filters.nm_dokter].filter(Boolean).join(' • ') || 'Semua poli dan dokter';
    document.getElementById('periodePendapatan').textContent = `${awal} – ${akhir} • ${cakupan}`;
  } catch (error) {
    showAlert(error.message || 'Terjadi kesalahan saat mengambil data.');
  } finally {
    button.disabled = false;
  }
}

function resetFilters(){
  const now = new Date();
  const today = now.toISOString().slice(0,10);
  document.getElementById('tgl1').value = `${today.slice(0,8)}01`;
  document.getElementById('tgl2').value = today;
  document.getElementById('kdPoli').value = '';
  document.getElementById('kdDokter').value = '';
}

document.addEventListener('DOMContentLoaded', async function(){
  table = $('#dtPendapatanRajal').DataTable({
    data: [],
    processing: true,
    responsive: true,
    autoWidth: false,
    pageLength: 12,
    lengthMenu: [[12,24,60,-1],[12,24,60,'Semua']],
    dom: 'lBfrtip',
    buttons: [
      { extend:'copyHtml5', text:'Copy' },
      { extend:'excelHtml5', text:'Excel', filename:'pendapatan_rawat_jalan_per_bulan' }
    ],
    columns: [
      { data:null, className:'text-center', orderable:false, render:(_data,_type,_row,meta)=>meta.row+1 },
      { data:'bulan', render:(value,type,row)=>type==='sort' ? row.periode : value },
      { data:'total', className:'text-right font-weight-bold', render:(value,type)=>type==='display' ? rupiah.format(Number(value || 0)) : Number(value || 0) }
    ],
    order: [[1,'asc']],
    language: {
      info:'Menampilkan _START_ sampai _END_ dari _TOTAL_ bulan',
      lengthMenu:'Tampilkan _MENU_ bulan',
      search:'Cari:',
      zeroRecords:'Data tidak ditemukan',
      emptyTable:'Belum ada data'
    }
  });

  try {
    await loadPoli();
    await loadDokter();
    await loadTotal();
  } catch (error) {
    showAlert(error.message || 'Halaman gagal dimuat.');
  }

  document.getElementById('btnCari').addEventListener('click', loadTotal);
  document.getElementById('btnReset').addEventListener('click', async function(){
    resetFilters();
    await loadDokter();
    await loadTotal();
  });
  document.getElementById('kdPoli').addEventListener('change', loadDokter);
  ['tgl1','tgl2'].forEach(id => document.getElementById(id).addEventListener('change', loadDokter));
});
</script>
