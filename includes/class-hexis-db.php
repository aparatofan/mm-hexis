<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Hexis_DB {
    public static function reviews_table() {
        global $wpdb;
        return $wpdb->prefix . 'hexis_reviews';
    }

    public static function assessments_table() {
        global $wpdb;
        return $wpdb->prefix . 'hexis_assessments';
    }

    public static function activate() {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset = $wpdb->get_charset_collate();
        $reviews = self::reviews_table();
        $assessments = self::assessments_table();

        $sql_reviews = "CREATE TABLE {$reviews} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            review_year smallint(4) unsigned NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'draft',
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            completed_at datetime NULL,
            PRIMARY KEY (id),
            UNIQUE KEY user_year (user_id, review_year)
        ) {$charset};";

        $sql_assessments = "CREATE TABLE {$assessments} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            review_id bigint(20) unsigned NOT NULL,
            virtue_key varchar(80) NOT NULL,
            direction tinyint(2) NULL,
            is_focus tinyint(1) NOT NULL DEFAULT 0,
            note text NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY review_virtue (review_id, virtue_key),
            KEY review_id (review_id)
        ) {$charset};";

        dbDelta( $sql_reviews );
        dbDelta( $sql_assessments );
        update_option( 'hexis_db_version', HEXIS_VERSION );
    }

    public static function get_virtues() {
        return array(
            'intellectual' => array(
                array('key'=>'open-mindedness','name'=>'Open-mindedness','definition'=>'The ability to consider new ideas, perspectives, and possibilities without prejudice.'),
                array('key'=>'critical-thinking','name'=>'Critical thinking','definition'=>'The ability to analyze and evaluate information logically and objectively.'),
                array('key'=>'wisdom','name'=>'Wisdom','definition'=>'The ability to apply knowledge and experience to make sound judgments.'),
                array('key'=>'reflection','name'=>'Reflection (with Tranquility)','definition'=>'The habit of thoughtfully reviewing one’s thoughts, actions, and decisions while maintaining calmness and clarity.'),
                array('key'=>'judgment','name'=>'Judgment','definition'=>'The capacity to make considered decisions and form sensible conclusions.'),
                array('key'=>'zest','name'=>'Zest','definition'=>'A sense of enthusiasm, energy, and wholehearted engagement with life.'),
                array('key'=>'curiosity','name'=>'Curiosity','definition'=>'The desire to explore, learn, and understand.'),
                array('key'=>'intellectual-humility','name'=>'Intellectual humility','definition'=>'Recognizing the limits of one’s knowledge and being open to learning from others.'),
                array('key'=>'creativity','name'=>'Creativity','definition'=>'The ability to think outside the box and generate original ideas.'),
                array('key'=>'silence','name'=>'Silence','definition'=>'The practice of speaking only when it adds value.'),
            ),
            'intrapersonal' => array(
                array('key'=>'diligence','name'=>'Diligence (Industry)','definition'=>'The commitment to hard work and sustained effort.'),
                array('key'=>'determination','name'=>'Determination (Resolution)','definition'=>'The ability to stay committed to a goal despite obstacles.'),
                array('key'=>'perseverance','name'=>'Perseverance','definition'=>'The ability to keep going despite difficulties.'),
                array('key'=>'grit','name'=>'Grit','definition'=>'The combination of passion and perseverance over the long term.'),
                array('key'=>'temperance','name'=>'Self-control over desires (Temperance)','definition'=>'The ability to regulate impulses and avoid excess.'),
                array('key'=>'self-discipline','name'=>'Self-discipline','definition'=>'The ability to stay focused and motivated to achieve long-term goals.'),
                array('key'=>'resilience','name'=>'Resilience','definition'=>'The ability to recover quickly from setbacks and adapt to change.'),
                array('key'=>'ambition','name'=>'Ambition','definition'=>'The drive to achieve success and strive for greater goals.'),
                array('key'=>'organization','name'=>'Organization (Order)','definition'=>'The habit of keeping things structured and efficient.'),
                array('key'=>'efficiency','name'=>'Efficiency','definition'=>'The ability to accomplish tasks with minimal waste of time, effort, or resources.'),
            ),
            'interpersonal' => array(
                array('key'=>'social-intelligence','name'=>'Social intelligence','definition'=>'The ability to navigate social situations and build meaningful relationships.'),
                array('key'=>'anger-moderation','name'=>'Self-control over anger (Moderation)','definition'=>'Managing emotions constructively and avoiding extremes in reactions.'),
                array('key'=>'empathy','name'=>'Empathy','definition'=>'The ability to understand and share the feelings of others.'),
                array('key'=>'kindness','name'=>'Kindness','definition'=>'The quality of being considerate and compassionate toward others.'),
                array('key'=>'gratitude','name'=>'Gratitude','definition'=>'The habit of appreciating what you have and recognizing the contributions of others.'),
                array('key'=>'generosity','name'=>'Generosity','definition'=>'The willingness to share time, resources, and support with others.'),
                array('key'=>'humility','name'=>'Humility','definition'=>'The recognition of one’s limitations and the willingness to learn from others.'),
                array('key'=>'forgiveness','name'=>'Forgiveness','definition'=>'The ability to let go of resentment and move forward.'),
                array('key'=>'compassion','name'=>'Compassion','definition'=>'A deep awareness of others’ suffering and a desire to alleviate it.'),
                array('key'=>'loyalty','name'=>'Loyalty','definition'=>'The quality of staying true to commitments and supporting others.'),
                array('key'=>'sincerity','name'=>'Sincerity','definition'=>'The quality of being honest and genuine.'),
                array('key'=>'justice','name'=>'Justice','definition'=>'The commitment to fairness and doing what is right.'),
            ),
        );
    }

    public static function get_or_create_review( $user_id, $year ) {
        global $wpdb;
        $table = self::reviews_table();
        $review = $wpdb->get_row( $wpdb->prepare("SELECT * FROM {$table} WHERE user_id=%d AND review_year=%d", $user_id, $year) );
        if ( $review ) return $review;
        $now = current_time( 'mysql' );
        $wpdb->insert( $table, array('user_id'=>$user_id,'review_year'=>$year,'status'=>'draft','created_at'=>$now,'updated_at'=>$now), array('%d','%d','%s','%s','%s') );
        return $wpdb->get_row( $wpdb->prepare("SELECT * FROM {$table} WHERE id=%d", $wpdb->insert_id) );
    }

    public static function get_assessments( $review_id ) {
        global $wpdb;
        $rows = $wpdb->get_results( $wpdb->prepare('SELECT * FROM ' . self::assessments_table() . ' WHERE review_id=%d', $review_id), OBJECT_K );
        $out = array();
        foreach ( $rows as $row ) $out[$row->virtue_key] = $row;
        return $out;
    }
}
