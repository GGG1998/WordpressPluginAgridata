<?php
/**
 * @package Agridata
 * @version 1.0
 */
/*
Plugin Name: Dystrybutorzy Agridata
Description: Plugin przeznaczony do WooCommerca, który pozwala na dodawanie dystrybutorów, a nasępnie odczyt z listy w formularzu
Author: Gabriel Domanowski
Version: 1.0
*/
define( 'LOCAL',True );
defined( 'ABSPATH' ) or die( 'Plugin file cannot be accessed directly.' );

if(LOCAL==True)
    define( 'AGRIDATA_PLUGIN_DIR',  get_option('siteurl').'/wp-content/plugins/distributors-agridata/');
else
    define( 'AGRIDATA_PLUGIN_DIR', plugin_dir_path( __FILE__  ));

if ( is_admin() && !class_exists('DistributorsAgridata')) {
    class DistributorsAgridata {
        
        /** 
         * Constructor 
         **/
        public function __construct() {
            add_action('init', array(&$this,'create_section'));
            add_action('admin_init',array(&$this, 'hide_editor'),100);
            add_action('add_meta_boxes', array($this, 'add_meta'));
            add_action('admin_enqueue_scripts', array(&$this, 'admin_scripts'));
            add_action('save_post', array(&$this, 'save_meta_data'));
            add_action('rest_api_init', array(&$this,'register_api'));
        }

        /** 
         * Load admin scripts 
         **/
        public function admin_scripts() {
            wp_enqueue_script('field-operator-script',AGRIDATA_PLUGIN_DIR.'js/fieldOperator.js',array( 'jquery' ));
        }

        /** 
         * HideEditor 
         **/
        public function hide_editor() {
            remove_post_type_support('distributors', 'editor');
        }

        /** 
         * Register distributor section 
         **/
        public function create_section() {
            // Register distributor
            $dist_labels = array(
                'name'               => 'Dystrybutorzy',
                'singular_name'      => 'Dystrybutor',
                'menu_name'          => 'Dystrybutorzy'
            );
            $dist_args = array(
                'labels'             => $dist_labels,
                'public'             => true,
                'capability_type'    => 'post',
                'has_archive'        => true,
                //'support'			 => array('title'),
                //'taxonomies'		 => array("category")
            );
            register_post_type('distributors', $dist_args);
        }

        /** 
         * Add meta box with fields 
         **/
        public function add_meta() {
            add_meta_box(
                'dist_post_class',
                __( 'Powiaty', 'Agridata_distributor' ),
                array($this,'display_meta_data'),
                'distributors'
            );
        }

        /** 
         * Add meta box with fields 
         * @param $post - object post
         **/
         public function display_meta_data($post) {
            $count=0;
            $data=get_post_meta($post->ID,'distributors_prov', true);
            $data_comm=get_post_meta($post->ID,'distributors_comm', true);
            
            wp_nonce_field(AGRIDATA_PLUGIN_DIR, 'dist_noncename');
            if(is_array($data)) :
                for($k=0; $k<count($data); $k++) :
                    //if(isset($d) || isset($d['community_pk'])) :
            ?>
                <script>
                   selectData(
                       <?php echo $count+1; ?>,
                       <?php echo $data[$k+1]['province_pk']; ?>,
                       <?php echo $count+1 ?>,
                       <?php echo $data_comm[$k+1]['community_pk']; ?>
                    );
                </script>
                <fieldset>
                    <legend>Grupa <?php echo $count+1; ?></legend>
                    <label for="province">Województwo*</label>
                    <select class="province" id="province_<?php echo $count+1; ?>" data-id="<?php echo $count; ?>">
                        <option>Pusto</option>
                    </select>
                    <label for="community">Gmina*</label>
                    <select class="community" id="community_<?php echo $count+1; ?>">
                        <option>Pusto</option>
                    </select>
                    <input class="prov" type="hidden" name="province_pk[<?php echo $count+1; ?>][province_pk]" value="<?php echo $data[$k+1]['province_pk'] ?>" data-id="<?php echo $count+1; ?>">
                    <input class="comm" type="hidden" name="community_pk[<?php echo $count+1; ?>][community_pk]" value="<?php echo $data_comm[$k+1]['community_pk'] ?>" data-id="<?php echo $count+1; ?>">
                    <button type="button" class="remove">Usuń</button>
                </fieldset>
            <?php
                    $count+=1;
                   // endif;
                endfor;
            endif;
            ?>
            <script> counter.set(<?php echo $count; ?>); </script>
            <span id="here_put"></span>
            <button type="button" id="add_data">Dodaj</button>
            <?php
         }

         /** 
         * Save data
         * @param $post - object post
         **/
         public function save_meta_data($post) {
             
            // if(!isset($_POST['dist_noncename']))
            //     return;
            // if ( !wp_verify_nonce( $_POST['dist_noncename'], ABSPATH) )
            //     return;
            $province_pk = $_POST['province_pk'];
            $community_pk = $_POST['community_pk'];

            update_post_meta($post,'distributors_prov',$province_pk);
            update_post_meta($post,'distributors_comm',$community_pk);
         }
    }
}
if(class_exists('DistributorsAgridata')) {
    $agridata = new DistributorsAgridata();
}

function getDistributors( $data ) {
    $args=array('post_type' => 'distributors','posts_per_page'=>-1);
    $distributors=new WP_Query($args);
    $arr=array();
    if ( $distributors->have_posts() ) :
        	while ( $distributors->have_posts() ) : $distributors->the_post();
                $title=get_the_title();
                $prov=get_post_meta(get_the_ID(),'distributors_prov',true);
                $common=get_post_meta(get_the_ID(),'distributors_comm',true);
                $values=array("title"=>$title,"province"=>array(),"community"=>array());
                foreach($prov as $d)
                   array_push($values['province'], $d['province_pk']);
                foreach($common as $d)
                   array_push($values['community'], $d['community_pk']);    
                array_push($arr,$values);
            endwhile;
            wp_reset_postdata();
    endif;
 
  if ( empty( $arr ) ) {
    return null;
  }
 
  $find_prov=$data['province_id'];
  $find_comm=$data['community_id'];

  $result=array();
  foreach($arr as $element) {
      for($i=0; $i<count($element['province']); $i++) {
          if($find_prov==$element['province'][$i] && $find_comm==$element['community'][$i])
            array_push($result,$element['title']);
      }
  }
  
  return json_encode($result);
}
add_action( 'rest_api_init', function () {
  register_rest_route( 'distributors-agridata/v1', '/distributor/(?P<province_id>\d+)/(?P<community_id>\d+)', array(
    'methods' => 'GET',
    'callback' => 'getDistributors',
  ) );
} );
?>