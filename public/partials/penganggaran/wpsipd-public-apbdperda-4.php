<?php
// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
global $wpdb;

$id_unit = '';
if (!empty($_GET) && !empty($_GET['id_unit'])) {
    $id_unit = $_GET['id_unit'];
}

$id_jadwal_lokal = '';
if (!empty($_GET) && !empty($_GET['id_jadwal_lokal'])) {
    $id_jadwal_lokal = $_GET['id_jadwal_lokal'];
} else {
    die('<h1 class="text-center">ID Jadwal Lokal Tidak Boleh Kosong!</h1>');
}

$input = shortcode_atts(array(
    'id_skpd' => $id_unit,
    'id_jadwal_lokal' => $id_jadwal_lokal,
    'tahun_anggaran' => '2022'
), $atts);

$jadwal_lokal = $wpdb->get_row($wpdb->prepare("
    SELECT 
        j.nama AS nama_jadwal,
        j.tahun_anggaran,
        j.status,
        j.status_jadwal_pergeseran,
        t.nama_tipe 
    FROM `data_jadwal_lokal` j
    INNER JOIN `data_tipe_perencanaan` t on t.id=j.id_tipe 
    WHERE j.id_jadwal_lokal=%d", $id_jadwal_lokal));

$_suffix = '';
$where_jadwal = '';
if ($jadwal_lokal->status == 1) {
    $_suffix = '_history';
    $where_jadwal = ' AND id_jadwal=' . $wpdb->prepare("%d", $id_jadwal_lokal);
}
$input['tahun_anggaran'] = $jadwal_lokal->tahun_anggaran;

$_suffix_sipd = '';
if (strpos($jadwal_lokal->nama_tipe, '_sipd') == false) {
    $_suffix_sipd = '_lokal';
}

$nama_skpd = '';
if ($input['id_skpd'] == 'all') {
    $data_skpd = $wpdb->get_results($wpdb->prepare("
        select 
            id_skpd 
        from data_unit
        where tahun_anggaran=%d
            and active=1
        order by kode_skpd ASC
    ", $input['tahun_anggaran']), ARRAY_A);
} else {
    $data_skpd = array(array('id_skpd' => $input['id_skpd']));
    $nama_skpd = $wpdb->get_var($wpdb->prepare("
        SELECT 
            CONCAT(kode_skpd, ' ',nama_skpd)
        FROM data_unit
        WHERE tahun_anggaran=%d
            and active=1
            and id_skpd=%d
    ", $input['tahun_anggaran'], $input['id_skpd']));
    if (!empty($nama_skpd)) {
        $nama_skpd = '<br>' . $nama_skpd;
    } else {
        die('<h1 class="text-center">SKPD tidak ditemukan!</h1>');
    }
}
$nama_pemda = get_option('_crb_daerah');
$nama_excel = 'REKAPITULASI BELANJA MENURUT URUSAN PEMERINTAH DAERAH, ORGANISASI, PROGRAM, KEGIATAN BESERTA HASIL DAN SUB KELUARAN<br>TAHUN ANGGARAN ' . $input['tahun_anggaran'] . '<br>' . strtoupper($nama_pemda) . $nama_skpd . '<br>' . $jadwal_lokal->nama_jadwal;

$body = '';
$total_operasi = 0;
$total_modal = 0;
$total_tak_terduga = 0;
$total_transfer = 0;
$total_all = 0;
$total_operasi_murni = 0;
$total_modal_murni = 0;
$total_tak_terduga_murni = 0;
$total_transfer_murni = 0;
$total_all_murni = 0;
$data_all = array();
foreach ($data_skpd as $skpd) {
    $sql = "
        SELECT 
            *
        FROM data_sub_keg_bl" . $_suffix_sipd . "" . $_suffix . "
        WHERE id_sub_skpd=%d
            AND tahun_anggaran=%d
            AND active=1
            " . $where_jadwal . "
            ORDER BY kode_giat ASC, kode_sub_giat ASC";
    $subkeg = $wpdb->get_results($wpdb->prepare($sql, $skpd['id_skpd'], $input['tahun_anggaran']), ARRAY_A);
    // die($wpdb->last_query);
    foreach ($subkeg as $kk => $sub) {
        $where_jadwal_new = '';
        if (!empty($where_jadwal)) {
            $where_jadwal_new = str_replace('AND id_jadwal', 'AND r.id_jadwal', $where_jadwal);
        }
        $rincian_all = $wpdb->get_results($wpdb->prepare("
            SELECT 
                r.rincian_murni,
                r.rincian,
                r.kode_akun
            FROM data_rka" . $_suffix_sipd . "" . $_suffix . " r
            WHERE r.tahun_anggaran=%d
                AND r.active=1
                AND r.kode_sbl=%s
                " . $where_jadwal_new . "
        ", $input['tahun_anggaran'], $sub['kode_sbl']), ARRAY_A);
        // die($wpdb->last_query);

        foreach ($rincian_all as $rincian) {
            if (empty($data_all[$sub['id_sub_skpd']])) {
                $data_all[$sub['id_sub_skpd']] = array(
                    'id' => $sub['id_sub_skpd'],
                    'kode' => $sub['kode_sub_skpd'],
                    'nama' => $sub['nama_sub_skpd'],
                    'operasi' => 0,
                    'modal' => 0,
                    'tak_terduga' => 0,
                    'transfer' => 0,
                    'total' => 0,
                    'operasi_murni' => 0,
                    'modal_murni' => 0,
                    'tak_terduga_murni' => 0,
                    'transfer_murni' => 0,
                    'total_murni' => 0,
                    'data' => array()
                );
            }
            if (empty($data_all[$sub['id_sub_skpd']]['data'][$sub['kode_program']])) {
                $data_all[$sub['id_sub_skpd']]['data'][$sub['kode_program']] = array(
                    'id' => $sub['id_program'],
                    'kode' => $sub['kode_program'],
                    'nama' => $sub['nama_program'],
                    'operasi' => 0,
                    'modal' => 0,
                    'tak_terduga' => 0,
                    'transfer' => 0,
                    'total' => 0,
                    'operasi_murni' => 0,
                    'modal_murni' => 0,
                    'tak_terduga_murni' => 0,
                    'transfer_murni' => 0,
                    'total_murni' => 0,
                    'data' => array()
                );
            }
            if (empty($data_all[$sub['id_sub_skpd']]['data'][$sub['kode_program']]['data'][$sub['kode_giat']])) {
                $data_all[$sub['id_sub_skpd']]['data'][$sub['kode_program']]['data'][$sub['kode_giat']] = array(
                    'id' => $sub['id_giat'],
                    'kode' => $sub['kode_giat'],
                    'nama' => $sub['nama_giat'],
                    'operasi' => 0,
                    'modal' => 0,
                    'tak_terduga' => 0,
                    'transfer' => 0,
                    'total' => 0,
                    'operasi_murni' => 0,
                    'modal_murni' => 0,
                    'tak_terduga_murni' => 0,
                    'transfer_murni' => 0,
                    'total_murni' => 0,
                    'data' => array()
                );
            }
            if (empty($data_all[$sub['id_sub_skpd']]['data'][$sub['kode_program']]['data'][$sub['kode_giat']]['data'][$sub['kode_sub_giat']])) {
                $data_all[$sub['id_sub_skpd']]['data'][$sub['kode_program']]['data'][$sub['kode_giat']]['data'][$sub['kode_sub_giat']] = array(
                    'operasi' => 0,
                    'modal' => 0,
                    'tak_terduga' => 0,
                    'transfer' => 0,
                    'total' => 0,
                    'operasi_murni' => 0,
                    'modal_murni' => 0,
                    'tak_terduga_murni' => 0,
                    'transfer_murni' => 0,
                    'total_murni' => 0,
                    'data' => array(),
                    'sub' => $sub
                );
            }

            $data_all[$sub['id_sub_skpd']]['total'] += $rincian['rincian'];
            $data_all[$sub['id_sub_skpd']]['total_murni'] += $rincian['rincian_murni'];

            $data_all[$sub['id_sub_skpd']]['data'][$sub['kode_program']]['total'] += $rincian['rincian'];
            $data_all[$sub['id_sub_skpd']]['data'][$sub['kode_program']]['total_murni'] += $rincian['rincian_murni'];

            $data_all[$sub['id_sub_skpd']]['data'][$sub['kode_program']]['data'][$sub['kode_giat']]['total'] += $rincian['rincian'];
            $data_all[$sub['id_sub_skpd']]['data'][$sub['kode_program']]['data'][$sub['kode_giat']]['total_murni'] += $rincian['rincian_murni'];

            $data_all[$sub['id_sub_skpd']]['data'][$sub['kode_program']]['data'][$sub['kode_giat']]['data'][$sub['kode_sub_giat']]['total'] += $rincian['rincian'];
            $data_all[$sub['id_sub_skpd']]['data'][$sub['kode_program']]['data'][$sub['kode_giat']]['data'][$sub['kode_sub_giat']]['total_murni'] += $rincian['rincian_murni'];

            $data_all[$sub['id_sub_skpd']]['data'][$sub['kode_program']]['data'][$sub['kode_giat']]['data'][$sub['kode_sub_giat']]['data'][] = $rincian;

            $rek = explode('.', $rincian['kode_akun']);
            $tipe_belanja = $rek[0] . '.' . $rek[1];
            if ($tipe_belanja == '5.1') {
                $data_all[$sub['id_sub_skpd']]['operasi'] += $rincian['rincian'];
                $data_all[$sub['id_sub_skpd']]['operasi_murni'] += $rincian['rincian_murni'];

                $data_all[$sub['id_sub_skpd']]['data'][$sub['kode_program']]['operasi'] += $rincian['rincian'];
                $data_all[$sub['id_sub_skpd']]['data'][$sub['kode_program']]['operasi_murni'] += $rincian['rincian_murni'];

                $data_all[$sub['id_sub_skpd']]['data'][$sub['kode_program']]['data'][$sub['kode_giat']]['operasi'] += $rincian['rincian'];
                $data_all[$sub['id_sub_skpd']]['data'][$sub['kode_program']]['data'][$sub['kode_giat']]['operasi_murni'] += $rincian['rincian_murni'];

                $data_all[$sub['id_sub_skpd']]['data'][$sub['kode_program']]['data'][$sub['kode_giat']]['data'][$sub['kode_sub_giat']]['operasi'] += $rincian['rincian'];
                $data_all[$sub['id_sub_skpd']]['data'][$sub['kode_program']]['data'][$sub['kode_giat']]['data'][$sub['kode_sub_giat']]['operasi_murni'] += $rincian['rincian_murni'];
            } else if ($tipe_belanja == '5.2') {
                $data_all[$sub['id_sub_skpd']]['modal'] += $rincian['rincian'];
                $data_all[$sub['id_sub_skpd']]['modal_murni'] += $rincian['rincian_murni'];

                $data_all[$sub['id_sub_skpd']]['data'][$sub['kode_program']]['modal'] += $rincian['rincian'];
                $data_all[$sub['id_sub_skpd']]['data'][$sub['kode_program']]['modal_murni'] += $rincian['rincian_murni'];

                $data_all[$sub['id_sub_skpd']]['data'][$sub['kode_program']]['data'][$sub['kode_giat']]['modal'] += $rincian['rincian'];
                $data_all[$sub['id_sub_skpd']]['data'][$sub['kode_program']]['data'][$sub['kode_giat']]['modal_murni'] += $rincian['rincian_murni'];

                $data_all[$sub['id_sub_skpd']]['data'][$sub['kode_program']]['data'][$sub['kode_giat']]['data'][$sub['kode_sub_giat']]['modal'] += $rincian['rincian'];
                $data_all[$sub['id_sub_skpd']]['data'][$sub['kode_program']]['data'][$sub['kode_giat']]['data'][$sub['kode_sub_giat']]['modal_murni'] += $rincian['rincian_murni'];
            } else if ($tipe_belanja == '5.3') {
                $data_all[$sub['id_sub_skpd']]['tak_terduga'] += $rincian['rincian'];
                $data_all[$sub['id_sub_skpd']]['tak_terduga_murni'] += $rincian['rincian_murni'];

                $data_all[$sub['id_sub_skpd']]['data'][$sub['kode_program']]['tak_terduga'] += $rincian['rincian'];
                $data_all[$sub['id_sub_skpd']]['data'][$sub['kode_program']]['tak_terduga_murni'] += $rincian['rincian_murni'];

                $data_all[$sub['id_sub_skpd']]['data'][$sub['kode_program']]['data'][$sub['kode_giat']]['tak_terduga'] += $rincian['rincian'];
                $data_all[$sub['id_sub_skpd']]['data'][$sub['kode_program']]['data'][$sub['kode_giat']]['tak_terduga_murni'] += $rincian['rincian_murni'];

                $data_all[$sub['id_sub_skpd']]['data'][$sub['kode_program']]['data'][$sub['kode_giat']]['data'][$sub['kode_sub_giat']]['tak_terduga'] += $rincian['rincian'];
                $data_all[$sub['id_sub_skpd']]['data'][$sub['kode_program']]['data'][$sub['kode_giat']]['data'][$sub['kode_sub_giat']]['tak_terduga_murni'] += $rincian['rincian_murni'];
            } else if ($tipe_belanja == '5.4') {
                $data_all[$sub['id_sub_skpd']]['transfer'] += $rincian['rincian'];
                $data_all[$sub['id_sub_skpd']]['transfer_murni'] += $rincian['rincian_murni'];

                $data_all[$sub['id_sub_skpd']]['data'][$sub['kode_program']]['transfer'] += $rincian['rincian'];
                $data_all[$sub['id_sub_skpd']]['data'][$sub['kode_program']]['transfer_murni'] += $rincian['rincian_murni'];

                $data_all[$sub['id_sub_skpd']]['data'][$sub['kode_program']]['data'][$sub['kode_giat']]['transfer'] += $rincian['rincian'];
                $data_all[$sub['id_sub_skpd']]['data'][$sub['kode_program']]['data'][$sub['kode_giat']]['transfer_murni'] += $rincian['rincian_murni'];

                $data_all[$sub['id_sub_skpd']]['data'][$sub['kode_program']]['data'][$sub['kode_giat']]['data'][$sub['kode_sub_giat']]['transfer'] += $rincian['rincian'];
                $data_all[$sub['id_sub_skpd']]['data'][$sub['kode_program']]['data'][$sub['kode_giat']]['data'][$sub['kode_sub_giat']]['transfer_murni'] += $rincian['rincian_murni'];
            }
        }
    }

    foreach ($data_all as $skpd) {
        if ($jadwal_lokal->status_jadwal_pergeseran == 'tidak_tampil') {
            $body .= '
                <tr data-id="' . $skpd['id'] . '" style="font-weight: bold;">
                    <td>' . $skpd['kode'] . '</td>
                    <td>' . $skpd['nama'] . '</td>
                    <td class="text-right">' . $this->_number_format($skpd['operasi']) . '</td>
                    <td class="text-right">' . $this->_number_format($skpd['modal']) . '</td>
                    <td class="text-right">' . $this->_number_format($skpd['tak_terduga']) . '</td>
                    <td class="text-right">' . $this->_number_format($skpd['transfer']) . '</td>
                    <td class="text-right">' . $this->_number_format($skpd['total']) . '</td>
                </tr>';
        } else {
            $body .= '
                <tr data-id="' . $skpd['id'] . '" style="font-weight: bold;">
                    <td>' . $skpd['kode'] . '</td>
                    <td>' . $skpd['nama'] . '</td>
                    <td class="text-right">' . $this->_number_format($skpd['operasi_murni']) . '</td>
                    <td class="text-right">' . $this->_number_format($skpd['modal_murni']) . '</td>
                    <td class="text-right">' . $this->_number_format($skpd['tak_terduga_murni']) . '</td>
                    <td class="text-right">' . $this->_number_format($skpd['transfer_murni']) . '</td>
                    <td class="text-right">' . $this->_number_format($skpd['total_murni']) . '</td>
                    <td class="text-right">' . $this->_number_format($skpd['operasi']) . '</td>
                    <td class="text-right">' . $this->_number_format($skpd['modal']) . '</td>
                    <td class="text-right">' . $this->_number_format($skpd['tak_terduga']) . '</td>
                    <td class="text-right">' . $this->_number_format($skpd['transfer']) . '</td>
                    <td class="text-right">' . $this->_number_format($skpd['total']) . '</td>
                </tr>';
        }
        foreach ($skpd['data'] as $program) {
            if ($jadwal_lokal->status_jadwal_pergeseran == 'tidak_tampil') {
                $body .= '
                    <tr data-id="' . $program['id'] . '" style="font-weight: bold;">
                        <td>' . $program['kode'] . '</td>
                        <td>' . $program['nama'] . '</td>
                        <td class="text-right">' . $this->_number_format($program['operasi']) . '</td>
                        <td class="text-right">' . $this->_number_format($program['modal']) . '</td>
                        <td class="text-right">' . $this->_number_format($program['tak_terduga']) . '</td>
                        <td class="text-right">' . $this->_number_format($program['transfer']) . '</td>
                        <td class="text-right">' . $this->_number_format($program['total']) . '</td>
                    </tr>';
            } else {
                $body .= '
                    <tr data-id="' . $program['id'] . '" style="font-weight: bold;">
                        <td>' . $program['kode'] . '</td>
                        <td>' . $program['nama'] . '</td>
                        <td class="text-right">' . $this->_number_format($program['operasi_murni']) . '</td>
                        <td class="text-right">' . $this->_number_format($program['modal_murni']) . '</td>
                        <td class="text-right">' . $this->_number_format($program['tak_terduga_murni']) . '</td>
                        <td class="text-right">' . $this->_number_format($program['transfer_murni']) . '</td>
                        <td class="text-right">' . $this->_number_format($program['total_murni']) . '</td>
                        <td class="text-right">' . $this->_number_format($program['operasi']) . '</td>
                        <td class="text-right">' . $this->_number_format($program['modal']) . '</td>
                        <td class="text-right">' . $this->_number_format($program['tak_terduga']) . '</td>
                        <td class="text-right">' . $this->_number_format($program['transfer']) . '</td>
                        <td class="text-right">' . $this->_number_format($program['total']) . '</td>
                    </tr>';
            }
            foreach ($program['data'] as $kegiatan) {
                if ($jadwal_lokal->status_jadwal_pergeseran == 'tidak_tampil') {
                    $body .= '
                        <tr data-id="' . $kegiatan['id'] . '" style="font-weight: bold;">
                            <td>' . $kegiatan['kode'] . '</td>
                            <td>' . $kegiatan['nama'] . '</td>
                            <td class="text-right">' . $this->_number_format($kegiatan['operasi']) . '</td>
                            <td class="text-right">' . $this->_number_format($kegiatan['modal']) . '</td>
                            <td class="text-right">' . $this->_number_format($kegiatan['tak_terduga']) . '</td>
                            <td class="text-right">' . $this->_number_format($kegiatan['transfer']) . '</td>
                            <td class="text-right">' . $this->_number_format($kegiatan['total']) . '</td>
                        </tr>';
                } else {
                    $body .= '
                        <tr data-id="' . $kegiatan['id'] . '" style="font-weight: bold;">
                            <td>' . $kegiatan['kode'] . '</td>
                            <td>' . $kegiatan['nama'] . '</td>
                            <td class="text-right">' . $this->_number_format($kegiatan['operasi_murni']) . '</td>
                            <td class="text-right">' . $this->_number_format($kegiatan['modal_murni']) . '</td>
                            <td class="text-right">' . $this->_number_format($kegiatan['tak_terduga_murni']) . '</td>
                            <td class="text-right">' . $this->_number_format($kegiatan['transfer_murni']) . '</td>
                            <td class="text-right">' . $this->_number_format($kegiatan['total_murni']) . '</td>
                            <td class="text-right">' . $this->_number_format($kegiatan['operasi']) . '</td>
                            <td class="text-right">' . $this->_number_format($kegiatan['modal']) . '</td>
                            <td class="text-right">' . $this->_number_format($kegiatan['tak_terduga']) . '</td>
                            <td class="text-right">' . $this->_number_format($kegiatan['transfer']) . '</td>
                            <td class="text-right">' . $this->_number_format($kegiatan['total']) . '</td>
                        </tr>';
                }
                foreach ($kegiatan['data'] as $kode => $data) {
                    $total_all += $data['total'];
                    $total_operasi += $data['operasi'];
                    $total_modal += $data['modal'];
                    $total_tak_terduga += $data['tak_terduga'];
                    $total_transfer += $data['transfer'];
                    $total_all_murni += $data['total_murni'];
                    $total_operasi_murni += $data['operasi_murni'];
                    $total_modal_murni += $data['modal_murni'];
                    $total_tak_terduga_murni += $data['tak_terduga_murni'];
                    $total_transfer_murni += $data['transfer_murni'];
                    $parts = explode(' ', $data['sub']['nama_sub_giat'], 2);
                    $nama_sub_giat = $parts[1];
                    if ($jadwal_lokal->status_jadwal_pergeseran == 'tidak_tampil') {
                        $body .= '
                            <tr data-kode="' . $kode . '">
                                <td>' . $data['sub']['kode_sub_giat'] . '</td>
                                <td>' . $nama_sub_giat . '</td>
                                <td class="text-right">' . $this->_number_format($data['operasi']) . '</td>
                                <td class="text-right">' . $this->_number_format($data['modal']) . '</td>
                                <td class="text-right">' . $this->_number_format($data['tak_terduga']) . '</td>
                                <td class="text-right">' . $this->_number_format($data['transfer']) . '</td>
                                <td class="text-right">' . $this->_number_format($data['total']) . '</td>
                            </tr>
                        ';
                    } else {
                        $body .= '
                            <tr data-kode="' . $kode . '">
                                <td>' . $data['sub']['kode_sub_giat'] . '</td>
                                <td>' . $nama_sub_giat . '</td>
                                <td class="text-right">' . $this->_number_format($data['operasi_murni']) . '</td>
                                <td class="text-right">' . $this->_number_format($data['modal_murni']) . '</td>
                                <td class="text-right">' . $this->_number_format($data['tak_terduga_murni']) . '</td>
                                <td class="text-right">' . $this->_number_format($data['transfer_murni']) . '</td>
                                <td class="text-right">' . $this->_number_format($data['total_murni']) . '</td>
                                <td class="text-right">' . $this->_number_format($data['operasi']) . '</td>
                                <td class="text-right">' . $this->_number_format($data['modal']) . '</td>
                                <td class="text-right">' . $this->_number_format($data['tak_terduga']) . '</td>
                                <td class="text-right">' . $this->_number_format($data['transfer']) . '</td>
                                <td class="text-right">' . $this->_number_format($data['total']) . '</td>
                            </tr>
                        ';
                    }
                }
            }
        }
    }
}
?>

<body>
    <div id="cetak" title="<?php echo $nama_excel; ?>" style="padding: 5px; overflow: auto;">
        <h2 class="text-center"><?php echo $nama_excel ?></h2>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th class="text-center align-middle" rowspan="5">No</th>
                    <th class="text-center align-middle" rowspan="5">Kode</th>
                    <th class="text-center align-middle" rowspan="5">Urusan / Bidang Urusan / Program / Kegiatan / Sub Kegiatan</th>
                    <th class="text-center align-middle" rowspan="5">Indikator Program / Kegiatan / Sub Kegiatan</th>
                </tr>
                <tr>
                    <th class="text-center align-middle" colspan="8">Capaian Kinerja dan Kerangka Pendanaan</th>
                </tr>
                <tr>
                    <th class="text-center align-middle" rowspan="3">Target 2024</th>
                </tr>
                <tr>
                    <th class="text-center align-middle" colspan="5">Pagu Indikatif Belanja</th>
                    <th class="text-center align-middle" rowspan="2">Lokasi</th>
                    <th class="text-center align-middle" rowspan="2">Sumber Dana</th>
                </tr>
                <tr>
                    <th class="text-center align-middle">Operasi</th>
                    <th class="text-center align-middle">Modal</th>
                    <th class="text-center align-middle">Tidak Terduga</th>
                    <th class="text-center align-middle">Transfer</th>
                    <th class="text-center align-middle">Total</th>
                </tr>
                <tr>
                    <th class="text-center align-middle">1</th>
                    <th class="text-center align-middle">2</th>
                    <th class="text-center align-middle">3</th>
                    <th class="text-center align-middle">4</th>
                    <th class="text-center align-middle">5</th>
                    <th class="text-center align-middle">6</th>
                    <th class="text-center align-middle">7</th>
                    <th class="text-center align-middle">8</th>
                    <th class="text-center align-middle">9</th>
                    <th class="text-center align-middle">10</th>
                    <th class="text-center align-middle">11</th>
                    <th class="text-center align-middle">12</th>
                </tr>
            </thead>
            <tbody>
                <?php echo $body; ?>
            </tbody>
        </table>
</body>
<script type="text/javascript">
    jQuery(document).ready(function() {
        run_download_excel();
    });
</script>