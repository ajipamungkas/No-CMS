<?php
class _Info extends CMS_Module_Info_Model{
    /////////////////////////////////////////////////////////////////////////////
    // Default Variables
    /////////////////////////////////////////////////////////////////////////////

    public $DEPENDENCIES = array();
    public $NAME         = 'gofrendi.noCMS.multisite';
    public $DESCRIPTION  = 'One codebase to rule them all ...';
    public $VERSION      = '0.0.2';


    /////////////////////////////////////////////////////////////////////////////
    // Default Functions
    /////////////////////////////////////////////////////////////////////////////

    // ACTIVATION
    public function do_activate(){
        $this->remove_all();
        $this->build_all();
    }

    // DEACTIVATION
    public function do_deactivate(){
        $this->backup_database(array(
            $this->cms_complete_table_name('subsite')
        ));
        $this->remove_all();
    }

    // UPGRADE
    public function do_upgrade($old_version){
        // Add your migration logic here.
        // table : subsite
        $table_name = $this->cms_complete_table_name('subsite');
        $field_list = $this->db->list_fields($table_name);
        $missing_fields = array(
            'user_id'=>array("type"=>'int', "constraint"=>10, "null"=>TRUE),
            'active'=>array("type"=>'int', "constraint"=>10, "null"=>TRUE, "default"=>1),
        );
        $fields = array();
        foreach($missing_fields as $key=>$value){
            if(!in_array($key, $field_list)){
                $fields[$key] = $value;
            }
        }
        $this->dbforge->add_column($table_name, $fields);
    }

    /////////////////////////////////////////////////////////////////////////////
    // Private Functions
    /////////////////////////////////////////////////////////////////////////////

    // REMOVE ALL NAVIGATIONS, WIDGETS, AND PRIVILEGES
    private function remove_all(){
        $module_path = $this->cms_module_path();

        if(CMS_SUBSITE == ''){
            // remove navigations
            $this->remove_navigation($this->cms_complete_navigation_name('add_subsite'));
            // remove privileges
            $this->remove_privilege('modify_subsite');
        }


        // remove parent of all navigations
        $this->remove_navigation($this->cms_complete_navigation_name('index'));

        // drop tables
        $this->dbforge->drop_table($this->cms_complete_table_name('subsite'), TRUE);
    }

    // CREATE ALL NAVIGATIONS, WIDGETS, AND PRIVILEGES
    private function build_all(){
        $module_path = $this->cms_module_path();

        // parent of all navigations
        $this->add_navigation($this->cms_complete_navigation_name('index'), 'Multisite',
            ($module_path == 'multisite'? $module_path : $module_path.'/multisite'), $this->PRIV_EVERYONE, NULL,
            NULL, 'Browse subsites', 'glyphicon-dashboard');

        if(CMS_SUBSITE == ''){
            // add privileges
            $this->add_privilege('modify_subsite', 'Modify subsite');
            // add navigations
            $this->add_navigation($this->cms_complete_navigation_name('add_subsite'), 'Add Subsite',
                $module_path.'/add_subsite', $this->PRIV_AUTHORIZED, $this->cms_complete_navigation_name('index'),
                NULL, 'Browse subsites', 'glyphicon-plus', NULL, 'default-one-column'
            );
        }


        // create tables
        $fields = array(
            'id'=> $this->TYPE_INT_UNSIGNED_AUTO_INCREMENT,
            'name'=> array("type"=>'varchar', "constraint"=>20, "null"=>TRUE),
            'use_subdomain'=> array("type"=>'int', "constraint"=>10, "null"=>TRUE),
            'aliases'=> array("type"=>'text', "null"=>TRUE),
            'logo'=> array("type"=>'varchar', "constraint"=>100, "null"=>TRUE),
            'description'=> array("type"=>'text', "null"=>TRUE),
            'modules'=>array("type"=>'text', "null"=>TRUE),
            'themes'=>array("type"=>'text', "null"=>TRUE),
            'user_id'=>array("type"=>'int', "constraint"=>10, "null"=>TRUE),
            'active'=>array("type"=>'int', "constraint"=>10, "null"=>TRUE, "default"=>1),
        );
        $this->dbforge->add_field($fields);
        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->create_table($this->cms_complete_table_name('subsite'));

    }

    // EXPORT DATABASE
    private function backup_database($table_names, $limit = 100){
        if($this->db->platform() == 'mysql' || $this->db->platform() == 'mysqli'){
            $module_path = $this->cms_module_path();
            $this->load->dbutil();
            $sql = '';

            // create DROP TABLE syntax
            for($i=count($table_names)-1; $i>=0; $i--){
                $table_name = $table_names[$i];
                $sql .= 'DROP TABLE IF EXISTS `'.$table_name.'`; '.PHP_EOL;
            }
            if($sql !='')$sql.= PHP_EOL;

            // create CREATE TABLE and INSERT syntax

            $prefs = array(
                    'tables'      => $table_names,
                    'ignore'      => array(),
                    'format'      => 'txt',
                    'filename'    => 'mybackup.sql',
                    'add_drop'    => FALSE,
                    'add_insert'  => TRUE,
                    'newline'     => PHP_EOL
                  );
            $sql.= @$this->dbutil->backup($prefs);

            //write file
            $file_name = 'backup_'.date('Y-m-d_G-i-s').'.sql';
            file_put_contents(
                    BASEPATH.'../modules/'.$module_path.'/assets/db/'.$file_name,
                    $sql
                );
        }

    }
}