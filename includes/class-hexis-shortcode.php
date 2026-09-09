<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Hexis_Shortcode {
    public static function init() {
        add_shortcode( 'hexis', array( __CLASS__, 'render' ) );
        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_assets' ) );
    }

    public static function register_assets() {
        wp_register_style( 'hexis', HEXIS_URL . 'assets/css/hexis.css', array(), HEXIS_VERSION );
        wp_register_script( 'hexis', HEXIS_URL . 'assets/js/hexis.js', array(), HEXIS_VERSION, true );
    }

    public static function render() {
        if ( ! is_user_logged_in() ) {
            return '<div class="hexis-login-note">Please log in to use Hexis.</div>';
        }

        wp_enqueue_style( 'hexis' );
        wp_enqueue_script( 'hexis' );

        $year = isset( $_GET['hexis_year'] ) ? absint( $_GET['hexis_year'] ) : (int) wp_date( 'Y' );
        $review = Hexis_DB::get_or_create_review( get_current_user_id(), $year );
        $assessments = Hexis_DB::get_assessments( $review->id );
        $virtues = Hexis_DB::get_virtues();

        wp_localize_script( 'hexis', 'HexisData', array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'hexis_nonce' ),
            'year'    => $year,
            'labels'  => array(
                '-3' => 'Strong regression',
                '-2' => 'Clear regression',
                '-1' => 'Slight regression',
                '0'  => 'Broadly stable',
                '1'  => 'Slight progress',
                '2'  => 'Clear progress',
                '3'  => 'Strong progress',
            ),
        ) );

        $group_labels = array(
            'intellectual' => 'Intellectual',
            'intrapersonal' => 'Intrapersonal',
            'interpersonal' => 'Interpersonal',
        );

        $rated_count = 0;
        foreach ( $assessments as $assessment ) {
            if ( $assessment->direction !== null ) $rated_count++;
        }

        ob_start();
        ?>
        <div class="hexis-app" data-year="<?php echo esc_attr( $year ); ?>">
            <header class="hexis-header">
                <div>
                    <div class="hexis-kicker">HEXIS</div>
                    <h1>Annual Review <?php echo esc_html( $year ); ?></h1>
                    <p>Reflect on the direction in which each part of your character has moved this year.</p>
                </div>
                <div class="hexis-status-panel">
                    <div class="hexis-progress-text"><strong class="hexis-rated-count"><?php echo esc_html( $rated_count ); ?></strong> of 32 reflected on</div>
                    <div class="hexis-progress"><span style="width:<?php echo esc_attr( ( $rated_count / 32 ) * 100 ); ?>%"></span></div>
                    <div class="hexis-save-state">Saved ✓</div>
                </div>
            </header>

            <?php foreach ( $virtues as $group_key => $group ) : ?>
                <section class="hexis-group">
                    <button type="button" class="hexis-group-toggle" aria-expanded="true">
                        <span><?php echo esc_html( $group_labels[$group_key] ); ?></span>
                        <span aria-hidden="true">−</span>
                    </button>
                    <div class="hexis-group-body">
                        <?php foreach ( $group as $virtue ) :
                            $a = isset( $assessments[$virtue['key']] ) ? $assessments[$virtue['key']] : null;
                            $direction = $a && $a->direction !== null ? (int) $a->direction : '';
                            $is_focus = $a ? (int) $a->is_focus : 0;
                            $note = $a ? $a->note : '';
                        ?>
                            <article class="hexis-virtue<?php echo $is_focus ? ' is-focus' : ''; ?>" data-virtue="<?php echo esc_attr( $virtue['key'] ); ?>" data-direction="<?php echo esc_attr( $direction ); ?>">
                                <div class="hexis-virtue-topline">
                                    <div>
                                        <h2><?php echo esc_html( $virtue['name'] ); ?></h2>
                                        <button type="button" class="hexis-definition-toggle" aria-expanded="false">ⓘ</button>
                                        <p class="hexis-definition" hidden><?php echo esc_html( $virtue['definition'] ); ?></p>
                                    </div>
                                    <label class="hexis-focus">
                                        <input type="checkbox" <?php checked( $is_focus, 1 ); ?>>
                                        <span>🔑 Focus</span>
                                    </label>
                                </div>

                                <div class="hexis-direction-wrap">
                                    <div class="hexis-arrow-stage" aria-label="Direction this year">
                                        <button type="button" class="hexis-arrow" aria-label="Set direction">
                                            <svg viewBox="0 0 120 40" role="img" aria-hidden="true">
                                                <line x1="12" y1="20" x2="95" y2="20" stroke="currentColor" stroke-width="5" stroke-linecap="round"></line>
                                                <polyline points="82,8 98,20 82,32" fill="none" stroke="currentColor" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"></polyline>
                                            </svg>
                                        </button>
                                    </div>
                                    <div class="hexis-angle-track" role="group" aria-label="Choose direction">
                                        <?php for ( $i = -3; $i <= 3; $i++ ) : ?>
                                            <button type="button" class="hexis-angle-point<?php echo $direction === $i ? ' is-selected' : ''; ?>" data-value="<?php echo esc_attr( $i ); ?>" aria-label="<?php echo esc_attr( self::direction_label( $i ) ); ?>"></button>
                                        <?php endfor; ?>
                                    </div>
                                    <div class="hexis-direction-label"><?php echo $direction === '' ? 'Not assessed yet' : esc_html( self::direction_label( $direction ) ); ?></div>
                                </div>

                                <button type="button" class="hexis-note-toggle"><?php echo $note ? 'Reflection ✓' : '+ Add reflection'; ?></button>
                                <div class="hexis-note-wrap" <?php echo $note ? '' : 'hidden'; ?>>
                                    <textarea class="hexis-note" rows="3" placeholder="What makes you say that?"><?php echo esc_textarea( $note ); ?></textarea>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endforeach; ?>

            <div class="hexis-footer-actions">
                <button type="button" class="hexis-complete-review">Complete Annual Review</button>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private static function direction_label( $direction ) {
        $labels = array(
            -3 => 'Strong regression',
            -2 => 'Clear regression',
            -1 => 'Slight regression',
             0 => 'Broadly stable',
             1 => 'Slight progress',
             2 => 'Clear progress',
             3 => 'Strong progress',
        );
        return isset( $labels[$direction] ) ? $labels[$direction] : '';
    }
}
