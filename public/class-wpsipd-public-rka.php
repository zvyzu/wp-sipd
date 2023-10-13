<?php

class Wpsipd_Public_RKA
{

    public function verifikasi_rka()
    {
        if (!empty($_GET) && !empty($_GET['post'])) {
            return '';
        }
        require_once WPSIPD_PLUGIN_PATH . 'public/partials/penganggaran/wpsipd-public-verifikasi-rka.php';
    }

    public function user_verikasi_rka()
    {
        if (!empty($_GET) && !empty($_GET['post'])) {
            return '';
        }
        require_once WPSIPD_PLUGIN_PATH . 'public/partials/penganggaran/wpsipd-public-user-verifikasi-rka.php';
    }

    function tambah_user_verifikator()
    {
        global $wpdb;
        $ret = array();
        $ret['status'] = 'success';
        $ret['message'] = 'Berhasil tambah user!';

        if (!empty($_POST)) {
            if (!empty($_POST['api_key']) && $_POST['api_key'] == get_option('_crb_api_key_extension')) {
                if(empty($_POST['username'])){
                    $ret['status'] = 'error';
                    $ret['message'] = 'Username tidak boleh kosong!';
                    die(json_encode($ret));
                }
                if(
                    !empty($_POST['id_user']) 
                    && empty($_POST['password'])
                ){
                    $ret['status'] = 'error';
                    $ret['message'] = 'Password tidak boleh kosong!';
                    die(json_encode($ret));
                }
                $username = $_POST['username'];
                $password = $_POST['password'];
                $nama = $_POST['nama'];
                $nomorwa = $_POST['nomorwa'];
                $email = $_POST['email'];
                $role = $_POST['role'];

                //validasi input
                if (strlen($username) < 5) {
                    $ret['status'] = 'error';
                    $ret['message'] = 'Username harus minimal 5 karakter.';
                    die(json_encode($ret));
                }
                if(
                    !empty($_POST['id_user'])
                    || !empty($password)
                ){
                    if (strlen($password) < 8) {
                        $ret['status'] = 'error';
                        $ret['message'] = 'Password harus minimal 8 karakter.';
                        die(json_encode($ret));
                    }
                    if (!preg_match('/[0-9]/', $password) || !preg_match('/[^a-zA-Z\d]/', $password)) {
                        $ret['status'] = 'error';
                        $ret['message'] = 'Password harus mengandung angka dan karakter unik.';
                        die(json_encode($ret));
                    }
                }
                if (!is_email($email)) {
                    $ret['status'] = 'error';
                    $ret['message'] = 'Format email tidak valid.';
                    die(json_encode($ret));
                }
                if (!preg_match('/^\+62\d{9,15}$/', $nomorwa)) {
                    $ret['status'] = 'error';
                    $ret['message'] = 'Nomor WhatsApp harus dimulai dengan +62, masukan 9 - 15 karakter!.';
                    die(json_encode($ret));
                }

                if(!empty($_POST['id_user'])){
                    $insert_user = $_POST['id_user'];
                    $current_user = get_userdata( $insert_user );
                    if(empty($current_user)){
                        $ret['status'] = 'error';
                        $ret['message'] = 'User dengan id='.$insert_user.', tidak ditemukan!';
                        die(json_encode($ret));
                    }
                }else{
                    $insert_user = username_exists($username);
                }

                $option = array(
                    'user_login' => $username,
                    'user_pass' => $password,
                    'user_email' => $email,
                    'first_name' => $nama,
                    'display_name' => $nama,
                    'role' => $role
                );
                //proses tambah user
                if (!$insert_user) {
                    $insert_user = wp_insert_user($option);
                    update_user_meta($insert_user, 'nomor_wa', $nomorwa);

                    if (is_wp_error($insert_user)) {
                        $ret['status'] = 'error';
                        $ret['message'] = $insert_user->get_error_message();
                    } else {
                        $ret['status'] = 'success';
                        $ret['message'] = 'User berhasil ditambahkan.';
                    }
                } else {
                    if(
                        !empty($_POST['id_user'])
                        && (
                            $current_user->user_login == $username
                            || !username_exists($username)
                        )
                    ){
                        if(empty($password)){
                            unset($option['user_pass']);
                        }
                        $option['ID'] = $insert_user;
                        wp_update_user( $option );
                        $ret['message'] = 'Berhasil update data!';
                    }else{
                        $ret['status'] = 'error';
                        $ret['message'] = 'Username sudah ada!';
                    }
                }
            } else {
                $ret['status'] = 'error';
                $ret['message'] = 'APIKEY tidak sesuai!';
            }
        } else {
            $ret['status'] = 'error';
            $ret['message'] = 'Format Salah!';
        }
        die(json_encode($ret));
    }

    function get_user_verifikator()
    {
        global $wpdb;
        $ret = array();
        $ret['status'] = 'success';
        $ret['message'] = 'Berhasil get user!';
        if (!empty($_POST)) {
            if (!empty($_POST['api_key']) && $_POST['api_key'] == get_option('_crb_api_key_extension')) {
                $user_id = um_user('ID');
                $user_meta = get_userdata($user_id);
                $params = $columns = $totalRecords = $data = array();
                $params = $_REQUEST;
                $roles = array('verifikator_bappeda', 'verifikator_bppkad', 'verifikator_pbj', 'verifikator_adbang', 'verifikator_inspektorat', 'verifikator_pupr');
                $args = array(
                    'role__in' => $roles,
                    'orderby' => 'user_nicename',
                    'order'   => 'ASC'
                );

                // check search value exist
                if (!empty($params['search']['value'])) {
                }

                $users = array();
                // get data user harus login sebagai admin
                if (in_array("administrator", $user_meta->roles)) {
                    $users = get_users($args);
                }

                $data_user = array();
                foreach ($users as $recKey => $recVal) {
                    $btn = '<a class="btn btn-sm btn-warning" onclick="edit_data(\'' . $recVal->ID . '\'); return false;" href="#" style="margin-right: 10px;" title="Edit Data">
                    <i class="dashicons dashicons-edit"></i></a>';
                    $btn .= '<a class="btn btn-sm btn-danger" onclick="delete_data(\'' . $recVal->ID . '\'); return false;" href="#" title="Hapus Data">
                    <i class="dashicons dashicons-trash"></i></a>';
                    $data_user[$recKey]['aksi'] = $btn;
                    $data_user[$recKey]['id'] = $recVal->ID;
                    $data_user[$recKey]['user'] = $recVal->user_login;
                    $data_user[$recKey]['nama'] = $recVal->display_name;
                    $data_user[$recKey]['email'] = $recVal->user_email;
                    $data_user[$recKey]['nomorwa'] = get_user_meta($recVal->ID, 'nomor_wa');
                    $data_user[$recKey]['role'] = implode(', ', $recVal->roles);
                    // $data_user[$recKey]['all'] = $recVal;
                }

                $json_data = array(
                    "draw"            => intval($params['draw']),
                    "recordsTotal"    => intval(count($data_user)),
                    "recordsFiltered" => intval(count($data_user)),
                    "data"            => $data_user
                );

                die(json_encode($json_data));
            } else {
                $ret['status'] = 'error';
                $ret['message'] = 'APIKEY tidak sesuai!';
            }
        } else {
            $ret['status'] = 'error';
            $ret['message'] = 'Format Salah!';
        }
        die(json_encode($ret));
    }

    function get_user_verifikator_by_id()
    {
        global $wpdb;
        $ret = array();
        $ret['status'] = 'success';
        $ret['message'] = 'Berhasil get user by id!';

        if (empty($_POST['id'])) {
            $ret['status'] = 'error';
            $ret['message'] = 'id user tidak boleh kosong!';
            die(json_encode($ret));
        }

        if (!empty($_POST)) {
            if (!empty($_POST['api_key']) && $_POST['api_key'] == get_option('_crb_api_key_extension')) {
                $user = get_userdata($_POST['id']);
                $new_user = array();
                $new_user['user_login'] = $user->data->id;
                $new_user['user_login'] = $user->data->user_login;
                $new_user['display_name'] = $user->data->display_name;
                $new_user['user_email'] = $user->data->user_email;
                $new_user['nomorwa'] = get_user_meta($user->ID, 'nomor_wa');
                $new_user['roles'] = $user->roles;
                $ret['data'] = $new_user;
            } else {
                $ret['status'] = 'error';
                $ret['message'] = 'APIKEY tidak sesuai!';
            }
        } else {
            $ret['status'] = 'error';
            $ret['message'] = 'Format Salah!';
        }
        die(json_encode($ret));
    }

    function delete_user_verifikator()
    {
        global $wpdb;

        $ret = array(
            'status' => 'success',
            'message' => 'Berhasil hapus data!',
            'data' => array()
        );

        $allowed_roles = array(
            'verifikator_bappeda',
            'verifikator_bppkad',
            'verifikator_pbj',
            'verifikator_adbang',
            'verifikator_inspektorat',
            'verifikator_pupr'
        );

        if (empty($_POST['id'])) {
            $ret['status'] = 'error';
            $ret['message'] = 'id user tidak boleh kosong!';
            die(json_encode($ret));
        }

        if (!empty($_POST)) {
            if (!empty($_POST['api_key']) && $_POST['api_key'] == get_option('_crb_api_key_extension')) {
                $current_user = get_userdata($_POST['id']);
                $user_roles = $current_user->roles;
                $is_allowed = false;
                foreach ($user_roles as $role) {
                    if (in_array($role, $allowed_roles)) {
                        $is_allowed = true;
                        break;
                    }
                }
                if ($is_allowed) {
                    if ($current_user->ID) {
                        wp_delete_user($current_user->ID);
                    } else {
                        $ret['status']  = 'error';
                        $ret['message'] = 'User tidak ditemukan!';
                    }
                } else {
                    $ret['status']  = 'error';
                    $ret['message'] = 'User ini tidak dapat dihapus!';
                }
            } else {
                $ret['status']  = 'error';
                $ret['message'] = 'Api key tidak ditemukan!';
            }
        } else {
            $ret['status']  = 'error';
            $ret['message'] = 'Format Salah!';
        }

        die(json_encode($ret));
    }
}
