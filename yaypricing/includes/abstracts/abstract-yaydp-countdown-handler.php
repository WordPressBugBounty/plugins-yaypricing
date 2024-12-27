<?php

namespace YAYDP\Abstracts;

abstract class YAYDP_Countdown_Handler {

	protected function __construct() {
		add_action( 'wp_body_open', array( $this, 'render_countdown' ), 10 );
	}

	abstract public function get_countdown_settings();

	abstract public function is_enabled();

	abstract protected function get_rules();

	public function render_countdown() {
		$all_events = $this->get_events();
		foreach ( $all_events['upcoming'] as $event ) {
			$this->display_upcoming_timer( $event );
		}

		foreach ( $all_events['will_end'] as $event ) {
			$this->display_will_end_timer( $event );
		}
	}

	public function get_events() {
		$result = array(
			'upcoming' => array(),
			'will_end' => array(),
		);
		foreach ( $this->get_rules() as $rule ) {
			if ( ! $rule->is_enabled() ) {
				continue;
			}

			if ( $rule->is_reach_limit_uses() ) {
				continue;
			}

			if ( ! $rule->is_enabled_schedule() ) {
				continue;
			}

			if ( $rule->is_upcoming() ) {
				$result['upcoming'][] = $rule;
				continue;
			}

			if ( $rule->is_end_in_future() ) {
				$result['will_end'][] = $rule;
				continue;
			}
		}
		return $result;
	}

	public function display_upcoming_timer( $event ) {
		$countdown_settings = $this->get_countdown_settings();
		$upcoming_text      = $countdown_settings['start_text'];
		$time               = $this->get_recurring_time( $event, 'start' );
		$this->replace_campaign_name( $upcoming_text, $event );
		$this->replace_timer( $upcoming_text, $time );
		\wc_get_template(
			'countdown/yaydp-upcoming-event-timer.php',
			array(
				'event'   => $event,
				'content' => $upcoming_text,
			),
			'',
			YAYDP_PLUGIN_PATH . 'includes/templates/'
		);
	}

	public function display_will_end_timer( $event ) {
		$countdown_settings = $this->get_countdown_settings();
		$end_text           = $countdown_settings['end_text'];
		$time               = $this->get_recurring_time( $event, 'end' );
		$this->replace_campaign_name( $end_text, $event );
		$this->replace_timer( $end_text, $time );
		\wc_get_template(
			'countdown/yaydp-will-end-event-timer.php',
			array(
				'event'   => $event,
				'content' => $end_text,
			),
			'',
			YAYDP_PLUGIN_PATH . 'includes/templates/'
		);
	}

	public function replace_campaign_name( &$raw_content, $rule ) {
		$raw_content = str_replace( '[campaign_name]', $rule->get_name(), $raw_content );
	}

	public function replace_timer( &$raw_content, $time ) {
		$raw_content = str_replace( '[timer]', '<span class="yaydp-event-countdown" data-timer="' . esc_attr( $time ) . '"></span>', $raw_content );
	}

	public function get_recurring_time( $event, $mode = 'start' ) {
		$time = $event->get_data()['schedule']['start'];
		if ( 'end' === $mode ) {
			$time = $event->get_data()['schedule']['end'];
		}
		if ( $event->is_enabled_schedule_recurring() ) {
			$recurring_type = $event->get_schedule_recurring_type();
			if ( 'weekly' === $recurring_type ) {
				$dayofweek = gmdate( 'l', strtotime( $time ) );
				$this_day  = new \DateTime( "this $dayofweek", new \DateTimeZone( wp_timezone_string() ) );
			}
			if ( 'monthly' === $recurring_type ) {
				$dateofmonth = intval( gmdate( 'j', strtotime( $time ) ) ) - 1;
				$this_day    = new \DateTime( 'first day of this month', new \DateTimeZone( wp_timezone_string() ) );
				$this_day->modify( "+$dateofmonth days" );
			}
			if ( 'yearly' === $recurring_type ) {
				// TODO: not correct now, but there is no user use this yearly.
				$this_day = new \DateTime( $time, new \DateTimeZone( wp_timezone_string() ) );
			}
			$time = $this_day->format( 'Y-m-d H:i:s' );
		}
		return $time;
	}

}
