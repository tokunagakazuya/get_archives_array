<?php
/**
* @function get_archives_array
* @param post_type(string) / period(string) / year(Y) / limit(int)
* @return array
*/
if(!function_exists('get_archives_array')){
    function get_archives_array($args = ''){
        global $wpdb, $wp_locale;

        $defaults = array(
            'post_type' => '',
            'period'  => 'monthly',
            'year' => '',
            'limit' => ''
        );
        $args = wp_parse_args($args, $defaults);
        extract($args, EXTR_SKIP);

        if($post_type == ''){
            $post_type = 'post';
        }elseif($post_type == 'any'){
            $post_types = get_post_types(array('public'=>true, '_builtin'=>false, 'show_ui'=>true));
            $post_type_ary = array();
            foreach($post_types as $post_type){
                $post_type_obj = get_post_type_object($post_type);
                if(!$post_type_obj){
                    continue;
                }

                if($post_type_obj->has_archive === true){
                    $slug = $post_type_obj->rewrite['slug'];
                }else{
                    $slug = $post_type_obj->has_archive;
                }

                array_push($post_type_ary, $slug);
            }

            $post_type = join("', '", $post_type_ary);
        }else{
            if(!post_type_exists($post_type)){
                return false;
            }
        }
        if($period == ''){
            $period = 'monthly';
        }
        if($year != ''){
            $year = intval($year);
            $year = " AND DATE_FORMAT(post_date, '%Y') = ".$year;
        }
        if($limit != ''){
            $limit = absint($limit);
            $limit = ' LIMIT '.$limit;
        }

        $where  = "WHERE post_type IN ('".$post_type."') AND post_status = 'publish'{$year}";
        $join   = "";
        $where  = apply_filters('getarchivesary_where', $where, $args);
        $join   = apply_filters('getarchivesary_join' , $join , $args);

        if($period == 'monthly'){
            $query = "SELECT YEAR(post_date) AS 'year', MONTH(post_date) AS 'month', count(ID) as posts FROM $wpdb->posts $join $where GROUP BY YEAR(post_date), MONTH(post_date) ORDER BY post_date DESC $limit";
        }elseif($period == 'daily'){
            $query = "SELECT YEAR(post_date) AS 'year', MONTH(post_date) AS 'month', DAY(post_date) AS 'day', count(ID) as posts FROM $wpdb->posts $join $where GROUP BY YEAR(post_date), MONTH(post_date), DAY(post_date) ORDER BY post_date DESC $limit";
        }elseif($period == 'yearly'){
            $query = "SELECT YEAR(post_date) AS 'year', count(ID) as posts FROM $wpdb->posts $join $where GROUP BY YEAR(post_date) ORDER BY post_date DESC $limit";
        }

        $key = md5($query);
        $cache = wp_cache_get('get_archives_array', 'general');
        if(!isset($cache[$key])){
            $arcresults = $wpdb->get_results($query);
            $cache[$key] = $arcresults;
            wp_cache_set('get_archives_array', $cache, 'general');
        }else{
            $arcresults = $cache[$key];
        }
        if($arcresults){
            $output = (array)$arcresults;
        }

        if(empty($output)){
            return false;
        }

        return $output;
    }
}
?>