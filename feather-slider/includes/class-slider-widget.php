<?php

namespace FeatherSlider;

use Elementor\Controls_Manager as Controls;
use Elementor\Group_Control_Typography as Typography;
use Elementor\Plugin;
use Elementor\Repeater;
use Elementor\Utils;
use Elementor\Widget_Base;

defined( 'ABSPATH' ) || exit;

final class Slider_Widget extends Widget_Base {
	public function get_name(): string {
		return HANDLE;
	}

	public function get_title(): string {
		return __( 'Feather Slider', 'feather-slider' );
	}

	public function get_icon(): string {
		return 'eicon-slides';
	}

	public function get_categories(): array {
		return [ 'general' ];
	}

	public function get_keywords(): array {
		return [ 'slider', 'carousel', 'hero', 'banner' ];
	}

	public function get_style_depends(): array {
		return [ HANDLE ];
	}

	public function get_script_depends(): array {
		return [ HANDLE ];
	}

	protected function register_controls(): void {
		$this->register_slide_controls();
		$this->register_playback_controls();
		$this->register_layout_controls();
		$this->register_text_controls();
		$this->register_panel_controls();
		$this->register_navigation_controls();
	}

	private function register_slide_controls(): void {
		$this->start_controls_section( 'slides_section', [ 'label' => __( 'Slides', 'feather-slider' ) ] );

		$repeater = new Repeater();
		$repeater->add_control( 'desktop_image', [
			'label' => __( 'Desktop image', 'feather-slider' ),
			'type'  => Controls::MEDIA,
		] );
		$repeater->add_control( 'mobile_image', [
			'label'       => __( 'Mobile image', 'feather-slider' ),
			'description' => __( 'Optional. Uses the desktop image when empty.', 'feather-slider' ),
			'type'        => Controls::MEDIA,
		] );
		$repeater->add_control( 'image_position', [
			'label'     => __( 'Image position', 'feather-slider' ),
			'type'      => Controls::SELECT,
			'default'   => 'center',
			'options'   => [
				'center' => __( 'Center', 'feather-slider' ),
				'top'    => __( 'Top', 'feather-slider' ),
				'bottom' => __( 'Bottom', 'feather-slider' ),
				'left'   => __( 'Left', 'feather-slider' ),
				'right'  => __( 'Right', 'feather-slider' ),
			],
			'selectors' => [ '{{WRAPPER}} {{CURRENT_ITEM}} .fs-image' => 'object-position: {{VALUE}};' ],
		] );
		$repeater->add_control( 'heading', [
			'label' => __( 'Heading', 'feather-slider' ),
			'type'  => Controls::TEXT,
			'label_block' => true,
		] );
		$repeater->add_control( 'description', [
			'label' => __( 'Description', 'feather-slider' ),
			'type'  => Controls::TEXTAREA,
		] );
		$repeater->add_control( 'button_label', [
			'label' => __( 'Button text', 'feather-slider' ),
			'type'  => Controls::TEXT,
		] );
		$repeater->add_control( 'button_link', [
			'label'   => __( 'Button link', 'feather-slider' ),
			'type'    => Controls::URL,
			'options' => [ 'url', 'is_external', 'nofollow' ],
		] );

		$horizontal = [
			'left'   => __( 'Left', 'feather-slider' ),
			'center' => __( 'Center', 'feather-slider' ),
			'right'  => __( 'Right', 'feather-slider' ),
		];
		$repeater->add_responsive_control( 'content_vertical', [
			'label'     => __( 'Vertical content position', 'feather-slider' ),
			'type'      => Controls::SELECT,
			'devices'   => [ 'desktop', 'mobile' ],
			'default'   => 'center',
			'options'   => [
				'start'  => __( 'Top', 'feather-slider' ),
				'center' => __( 'Middle', 'feather-slider' ),
				'end'    => __( 'Bottom', 'feather-slider' ),
			],
			'selectors' => [ '{{WRAPPER}} {{CURRENT_ITEM}} .fs-layer' => 'align-items: {{VALUE}};' ],
		] );
		$repeater->add_responsive_control( 'content_horizontal', [
			'label'                => __( 'Horizontal content position', 'feather-slider' ),
			'type'                 => Controls::SELECT,
			'devices'              => [ 'desktop', 'mobile' ],
			'default'              => 'left',
			'options'              => $horizontal,
			'selectors_dictionary' => [ 'left' => 'flex-start', 'right' => 'flex-end' ],
			'selectors'            => [ '{{WRAPPER}} {{CURRENT_ITEM}} .fs-layer' => 'justify-content: {{VALUE}};' ],
		] );
		$repeater->add_responsive_control( 'text_alignment', [
			'label'     => __( 'Text alignment', 'feather-slider' ),
			'type'      => Controls::SELECT,
			'devices'   => [ 'desktop', 'mobile' ],
			'default'   => 'left',
			'options'   => $horizontal,
			'selectors' => [ '{{WRAPPER}} {{CURRENT_ITEM}} .fs-panel' => 'text-align: {{VALUE}};' ],
		] );
		$this->add_control( 'slides', [
			'label'         => __( 'Slides', 'feather-slider' ),
			'type'          => Controls::REPEATER,
			'fields'        => $repeater->get_controls(),
			'title_field'   => '{{{ _.escape( heading || "" ) }}}',
			'prevent_empty' => false,
			'default'       => [[
				'desktop_image' => [ 'url' => Utils::get_placeholder_image_src() ],
				'heading'       => __( 'Your slide heading', 'feather-slider' ),
			]],
		] );
		$this->add_control( 'accessible_name', [
			'label'   => __( 'Accessible slider name', 'feather-slider' ),
			'type'    => Controls::TEXT,
			'default' => __( 'Featured slides', 'feather-slider' ),
		] );
		$this->end_controls_section();
	}

	private function register_playback_controls(): void {
		$this->start_controls_section( 'playback_section', [ 'label' => __( 'Playback and navigation', 'feather-slider' ) ] );
		$this->add_control( 'effect', [
			'label'   => __( 'Transition', 'feather-slider' ),
			'type'    => Controls::SELECT,
			'default' => 'fade-in',
			'options' => [
				'slide-left'  => __( 'Slide Left', 'feather-slider' ),
				'slide-right' => __( 'Slide Right', 'feather-slider' ),
				'zoom-in'     => __( 'Zoom In', 'feather-slider' ),
				'fade-in'     => __( 'Fade In', 'feather-slider' ),
				'fade-out'    => __( 'Fade Out', 'feather-slider' ),
			],
		] );
		$this->add_control( 'duration', [
			'label'   => __( 'Transition duration (ms)', 'feather-slider' ),
			'type'    => Controls::NUMBER,
			'default' => 400,
			'min'     => 0,
			'max'     => 3000,
			'step'    => 50,
		] );
		$this->add_control( 'easing', [
			'label'   => __( 'Transition easing', 'feather-slider' ),
			'type'    => Controls::SELECT,
			'default' => 'ease-in-out',
			'options' => [
				'ease-in-out' => __( 'Ease in and out', 'feather-slider' ),
				'ease-in'     => __( 'Ease in', 'feather-slider' ),
				'ease-out'    => __( 'Ease out', 'feather-slider' ),
				'linear'      => __( 'Linear', 'feather-slider' ),
			],
		] );
		$this->add_toggle( 'autoplay', __( 'Autoplay', 'feather-slider' ) );
		$this->add_control( 'autoplay_delay', [
			'label'     => __( 'Autoplay delay (ms)', 'feather-slider' ),
			'type'      => Controls::NUMBER,
			'default'   => 5000,
			'min'       => 500,
			'max'       => 60000,
			'step'      => 100,
			'condition' => [ 'autoplay' => 'yes' ],
		] );
		$this->add_toggle( 'pause_hover', __( 'Pause on hover', 'feather-slider' ), true, [ 'autoplay' => 'yes' ] );
		$this->add_toggle( 'loop', __( 'Loop', 'feather-slider' ), true );
		$this->add_toggle( 'show_arrows', __( 'Show arrows', 'feather-slider' ), true );
		$this->add_toggle( 'show_dots', __( 'Show pagination', 'feather-slider' ), true );
		$this->end_controls_section();
	}

	private function add_toggle( string $name, string $label, bool $enabled = false, array $condition = [] ): void {
		$this->add_control( $name, [
			'label'        => $label,
			'type'         => Controls::SWITCHER,
			'return_value' => 'yes',
			'default'      => $enabled ? 'yes' : '',
			'condition'    => $condition,
		] );
	}

	private function register_layout_controls(): void {
		$this->start_controls_section( 'layout_section', [
			'label' => __( 'Slider size', 'feather-slider' ),
			'tab'   => Controls::TAB_STYLE,
		] );
		$this->start_controls_tabs( 'size_devices' );
		foreach ( $this->device_tabs() as $device => $label ) {
			$this->start_controls_tab( 'size_' . $device, [ 'label' => $label ] );
			$selector = $this->device_selector( $device ) . ' .fs-viewport';
			$this->add_control( 'ratio_' . $device, [
				'label'     => __( 'Aspect ratio', 'feather-slider' ),
				'type'      => Controls::SELECT,
				'default'   => 'mobile' === $device ? '4/5' : '16/9',
				'options'   => [ '21/9' => '21:9', '16/9' => '16:9', '3/2' => '3:2', '4/3' => '4:3', '1/1' => '1:1', '4/5' => '4:5', '9/16' => '9:16' ],
				'selectors' => [ $selector => 'aspect-ratio: {{VALUE}};' ],
			] );
			$this->add_control( 'size_mode_' . $device, [
				'label'                => __( 'Size mode', 'feather-slider' ),
				'type'                 => Controls::SELECT,
				'default'              => 'ratio',
				'options'              => [
					'ratio' => __( 'Use aspect ratio', 'feather-slider' ),
					'fixed' => __( 'Use fixed height', 'feather-slider' ),
				],
				'selectors_dictionary' => [
					'ratio' => 'auto',
					'fixed' => 'var(--fs-height-' . $device . ', auto)',
				],
				'selectors'            => [ $selector => 'height: {{VALUE}};' ],
			] );
			$this->add_control( 'height_' . $device, [
				'label'      => __( 'Height', 'feather-slider' ),
				'type'       => Controls::SLIDER,
				'size_units' => [ 'px', 'vh' ],
				'default'    => [ 'size' => 'mobile' === $device ? 480 : 560, 'unit' => 'px' ],
				'range'      => [ 'px' => [ 'min' => 160, 'max' => 1600 ], 'vh' => [ 'min' => 20, 'max' => 100 ] ],
				'condition'  => [ 'size_mode_' . $device => 'fixed' ],
				'selectors'  => [ $selector => '--fs-height-' . $device . ': {{SIZE}}{{UNIT}};' ],
			] );
			$this->end_controls_tab();
		}
		$this->end_controls_tabs();
		$this->add_length( 'frame_radius', __( 'Slider corner radius', 'feather-slider' ), '{{WRAPPER}} .fs-viewport', 'border-radius', 0, 100 );
		$this->end_controls_section();
	}

	private function register_text_controls(): void {
		$this->start_controls_section( 'text_section', [
			'label' => __( 'Text and button', 'feather-slider' ),
			'tab'   => Controls::TAB_STYLE,
		] );
		foreach ( [
			'heading'     => __( 'Heading', 'feather-slider' ),
			'description' => __( 'Description', 'feather-slider' ),
			'button'      => __( 'Button', 'feather-slider' ),
		] as $name => $label ) {
			$selector = '{{WRAPPER}} .fs-' . $name;
			$this->add_control( $name . '_style_label', [
				'label'     => $label,
				'type'      => Controls::HEADING,
				'separator' => 'before',
			] );
			$this->add_color( $name . '_color', __( 'Text color', 'feather-slider' ), $selector, 'color' );
			$this->add_group_control( Typography::get_type(), [
				'name'     => $name . '_typography',
				'selector' => $selector,
			] );
		}
		$this->add_color( 'button_background', __( 'Button background', 'feather-slider' ), '{{WRAPPER}} .fs-button', 'background-color' );
		$this->add_color( 'button_hover_background', __( 'Button hover background', 'feather-slider' ), '{{WRAPPER}} .fs-button:hover', 'background-color' );
		$this->add_length( 'button_radius', __( 'Button corner radius', 'feather-slider' ), '{{WRAPPER}} .fs-button', 'border-radius', 6, 60 );
		$this->end_controls_section();
	}

	private function register_panel_controls(): void {
		$this->start_controls_section( 'panel_section', [
			'label' => __( 'Content background', 'feather-slider' ),
			'tab'   => Controls::TAB_STYLE,
		] );
		$this->start_controls_tabs( 'panel_devices' );
		foreach ( $this->device_tabs() as $device => $label ) {
			$this->start_controls_tab( 'panel_' . $device, [ 'label' => $label ] );
			$panel = $this->device_selector( $device ) . ' .fs-panel';
			$mode = 'panel_mode_' . $device;
			$this->add_control( $mode, [
				'label'                => __( 'Background mode', 'feather-slider' ),
				'type'                 => Controls::SELECT,
				'default'              => 'glass',
				'options'              => [
					'none'  => __( 'None', 'feather-slider' ),
					'solid' => __( 'Solid', 'feather-slider' ),
					'blur'  => __( 'Blur', 'feather-slider' ),
					'glass' => __( 'Glass', 'feather-slider' ),
				],
				'selectors_dictionary' => [
					'none'  => '--fs-layer-display: none',
					'solid' => '--fs-layer-display: block; --fs-layer-filter: none; --fs-layer-shine: none; --fs-layer-fill: var(--fs-tinted-fill); --fs-layer-stroke: var(--fs-stroke, transparent)',
					'blur'  => '--fs-layer-display: block; --fs-layer-filter: var(--fs-blur-filter); --fs-layer-shine: none; --fs-layer-fill: var(--fs-glass-fill); --fs-layer-stroke: var(--fs-stroke, transparent)',
					'glass' => '--fs-layer-display: block; --fs-layer-filter: var(--fs-blur-filter); --fs-layer-shine: var(--fs-glass-shine); --fs-layer-fill: var(--fs-glass-fill); --fs-layer-stroke: var(--fs-stroke, rgba(255, 255, 255, .32))',
				],
				'selectors' => [ $panel => '{{VALUE}};' ],
			] );
			$background = [ $mode . '!' => 'none' ];
			$blur = [ $mode => [ 'blur', 'glass' ] ];
			$this->add_color( 'panel_color_' . $device, __( 'Background color', 'feather-slider' ), $panel, '--fs-tint', $background, '#10182a' );
			$this->add_length( 'panel_opacity_' . $device, __( 'Background opacity', 'feather-slider' ), $panel, '--fs-opacity', 58, 100, $background, '%' );
			$this->add_length( 'panel_blur_' . $device, __( 'Backdrop blur', 'feather-slider' ), $panel, '--fs-blur', 14, 40, $blur );
			$this->add_color( 'panel_border_color_' . $device, __( 'Border color', 'feather-slider' ), $panel, '--fs-stroke', $background, 'rgba(255, 255, 255, 0.32)' );
			$this->add_length( 'panel_border_width_' . $device, __( 'Border width', 'feather-slider' ), $panel, '--fs-stroke-width', 1, 12, $background );
			$this->add_length( 'panel_radius_' . $device, __( 'Corner radius', 'feather-slider' ), $panel, 'border-radius', 18, 80, $background );
			$this->add_control( 'panel_padding_' . $device, [
				'label'      => __( 'Padding', 'feather-slider' ),
				'type'       => Controls::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'default'    => [ 'unit' => 'px', 'top' => 24, 'right' => 24, 'bottom' => 24, 'left' => 24, 'isLinked' => true ],
				'selectors'  => [ $panel => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
			] );
			$this->add_control( 'panel_shadow_' . $device, [
				'label'        => __( 'Shadow', 'feather-slider' ),
				'type'         => Controls::SELECT,
				'default'      => 'on',
				'options'      => [ 'on' => __( 'On', 'feather-slider' ), 'off' => __( 'Off', 'feather-slider' ) ],
				'selectors_dictionary' => [ 'on' => '0 12px 36px rgba(0, 0, 0, .23)', 'off' => 'none' ],
				'selectors' => [ $panel => '--fs-panel-shadow: {{VALUE}};' ],
				'condition' => $background,
			] );
			$this->end_controls_tab();
		}
		$this->end_controls_tabs();
		$this->end_controls_section();
	}

	private function register_navigation_controls(): void {
		$this->start_controls_section( 'navigation_section', [
			'label' => __( 'Arrows and pagination', 'feather-slider' ),
			'tab'   => Controls::TAB_STYLE,
		] );
		$this->add_control( 'arrow_position', [
			'label'   => __( 'Vertical arrow position', 'feather-slider' ),
			'type'    => Controls::SELECT,
			'default' => 'center',
			'options' => [
				'top'    => __( 'Top', 'feather-slider' ),
				'center' => __( 'Center', 'feather-slider' ),
				'bottom' => __( 'Bottom', 'feather-slider' ),
				'custom' => __( 'Custom', 'feather-slider' ),
			],
		] );
		$this->add_length( 'arrow_offset', __( 'Offset from top (px)', 'feather-slider' ), '{{WRAPPER}} .fs-viewport', '--fs-arrow-offset', 16, 1200, [ 'arrow_position' => 'custom' ] );
		$this->add_length( 'arrow_size', __( 'Arrow size', 'feather-slider' ), '{{WRAPPER}} .fs-viewport', '--fs-arrow-size', 44, 80 );
		$this->add_length( 'arrow_radius', __( 'Arrow corner radius', 'feather-slider' ), '{{WRAPPER}} .fs-arrow', 'border-radius', 30, 60 );
		$this->add_color( 'arrow_color', __( 'Arrow color', 'feather-slider' ), '{{WRAPPER}} .fs-arrow', 'color' );
		$this->add_color( 'arrow_background', __( 'Arrow background', 'feather-slider' ), '{{WRAPPER}} .fs-arrow', 'background-color' );
		$this->add_length( 'dot_width', __( 'Capsule width', 'feather-slider' ), '{{WRAPPER}} .fs-viewport', '--fs-dot-width', 24, 64 );
		$this->add_length( 'dot_height', __( 'Capsule height', 'feather-slider' ), '{{WRAPPER}} .fs-viewport', '--fs-dot-height', 8, 28 );
		$this->add_length( 'dot_gap', __( 'Capsule gap', 'feather-slider' ), '{{WRAPPER}} .fs-viewport', '--fs-dot-gap', 4, 30 );
		$this->add_length( 'dot_radius', __( 'Capsule corner radius', 'feather-slider' ), '{{WRAPPER}} .fs-viewport', '--fs-dot-radius', 12, 32 );
		$this->add_color( 'dot_active_color', __( 'Active capsule color', 'feather-slider' ), '{{WRAPPER}} .fs-viewport', '--fs-dot-active' );
		$this->add_color( 'dot_outline_color', __( 'Inactive capsule outline', 'feather-slider' ), '{{WRAPPER}} .fs-viewport', '--fs-dot-outline' );
		$this->end_controls_section();
	}

	private function device_tabs(): array {
		return [
			'desktop' => __( 'Desktop', 'feather-slider' ),
			'mobile'  => __( 'Mobile', 'feather-slider' ),
		];
	}

	private function device_selector( string $device ): string {
		return ( 'mobile' === $device ? '(mobile) ' : '' ) . '{{WRAPPER}}';
	}

	private function add_color( string $name, string $label, string $selector, string $property, array $condition = [], string $default = '' ): void {
		$this->add_control( $name, [
			'label'     => $label,
			'type'      => Controls::COLOR,
			'default'   => $default,
			'selectors' => [ $selector => $property . ': {{VALUE}};' ],
			'condition' => $condition,
		] );
	}

	private function add_length( string $name, string $label, string $selector, string $property, int $default, int $max, array $condition = [], string $unit = 'px' ): void {
		$this->add_control( $name, [
			'label'      => $label,
			'type'       => Controls::SLIDER,
			'size_units' => [ $unit ],
			'default'    => [ 'size' => $default, 'unit' => $unit ],
			'range'      => [ $unit => [ 'min' => 0, 'max' => $max ] ],
			'selectors'  => [ $selector => $property . ': {{SIZE}}{{UNIT}};' ],
			'condition'  => $condition,
		] );
	}

	protected function render(): void {
		$settings = $this->get_settings_for_display();
		$slides = array_values( array_filter( $settings['slides'] ?? [], static function ( array $slide ): bool {
			return ! empty( $slide['desktop_image']['url'] ) || ! empty( $slide['desktop_image']['id'] )
				|| ! empty( $slide['mobile_image']['url'] ) || ! empty( $slide['mobile_image']['id'] )
				|| '' !== trim( (string) ( $slide['heading'] ?? '' ) )
				|| '' !== trim( (string) ( $slide['description'] ?? '' ) )
				|| ( ! empty( $slide['button_label'] ) && ! empty( $slide['button_link']['url'] ) );
		} ) );

		if ( ! $slides ) {
			if ( Plugin::$instance->editor->is_edit_mode() ) {
				echo '<p>' . esc_html__( 'Add an image or some content to create a slide.', 'feather-slider' ) . '</p>';
			}
			return;
		}

		$count = count( $slides );
		$multiple = $count > 1;
		$autoplay = $multiple && $this->enabled( $settings, 'autoplay', false );
		$loop = $this->enabled( $settings, 'loop' );
		$effect = $this->option( $settings['effect'] ?? '', [ 'slide-left', 'slide-right', 'zoom-in', 'fade-in', 'fade-out' ], 'fade-in' );
		$easing = $this->option( $settings['easing'] ?? '', [ 'linear', 'ease-in', 'ease-out', 'ease-in-out' ], 'ease-in-out' );
		$arrows = $this->option( $settings['arrow_position'] ?? '', [ 'top', 'center', 'bottom', 'custom' ], 'center' );
		$duration = $this->bounded_number( $settings['duration'] ?? null, 400, 0, 3000 );
		$delay = $this->bounded_number( $settings['autoplay_delay'] ?? null, 5000, 500, 60000 );
		$mobile = Plugin::$instance->breakpoints->get_breakpoints( 'mobile' );
		$breakpoint = is_object( $mobile ) ? (int) $mobile->get_value() : 767;
		$name = trim( (string) ( $settings['accessible_name'] ?? '' ) ) ?: __( 'Featured slides', 'feather-slider' );
		?>
		<feather-slider class="fs-slider" role="region"
			aria-label="<?php echo esc_attr( $name ); ?>"
			aria-roledescription="<?php esc_attr_e( 'carousel', 'feather-slider' ); ?>"
			data-feather-build="<?php echo esc_attr( BUILD ); ?>"
			data-effect="<?php echo esc_attr( $effect ); ?>"
			data-duration="<?php echo esc_attr( (string) $duration ); ?>"
			data-delay="<?php echo esc_attr( (string) $delay ); ?>"
			data-loop="<?php echo $loop ? '1' : '0'; ?>"
			data-autoplay="<?php echo $autoplay ? '1' : '0'; ?>"
			data-pause-hover="<?php echo $this->enabled( $settings, 'pause_hover' ) ? '1' : '0'; ?>"
			style="<?php echo esc_attr( "--fs-duration: {$duration}ms; --fs-easing: {$easing};" ); ?>">
			<div class="fs-viewport" data-arrow-position="<?php echo esc_attr( $arrows ); ?>">
				<?php if ( $autoplay ) : ?>
					<button type="button" class="fs-playback" hidden
						data-pause-label="<?php esc_attr_e( 'Pause slideshow', 'feather-slider' ); ?>"
						data-play-label="<?php esc_attr_e( 'Play slideshow', 'feather-slider' ); ?>"
						aria-label="<?php esc_attr_e( 'Pause slideshow', 'feather-slider' ); ?>">
						<span class="fs-playback-icon" aria-hidden="true"></span>
					</button>
				<?php endif; ?>
				<div class="fs-track"<?php echo $multiple ? ' tabindex="0"' : ''; ?>>
					<?php foreach ( $slides as $index => $slide ) : ?>
						<?php $this->render_slide( $slide, $index, $count, $breakpoint ); ?>
					<?php endforeach; ?>
				</div>
				<?php if ( $multiple ) : ?>
					<?php if ( $this->enabled( $settings, 'show_arrows' ) ) : ?>
						<button type="button" class="fs-arrow fs-previous" aria-label="<?php esc_attr_e( 'Previous slide', 'feather-slider' ); ?>" hidden><span aria-hidden="true"></span></button>
						<button type="button" class="fs-arrow fs-next" aria-label="<?php esc_attr_e( 'Next slide', 'feather-slider' ); ?>" hidden><span aria-hidden="true"></span></button>
					<?php endif; ?>
					<?php if ( $this->enabled( $settings, 'show_dots' ) ) : ?>
						<div class="fs-dots" role="group" aria-label="<?php esc_attr_e( 'Choose a slide', 'feather-slider' ); ?>" hidden>
							<?php foreach ( $slides as $index => $slide ) : ?>
								<button type="button" class="fs-dot" data-index="<?php echo esc_attr( (string) $index ); ?>"
									aria-label="<?php echo esc_attr( sprintf( __( 'Go to slide %d', 'feather-slider' ), $index + 1 ) ); ?>"
									<?php echo 0 === $index ? 'aria-current="true"' : ''; ?>></button>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
					<span class="fs-status" aria-live="polite" aria-atomic="true"></span>
				<?php endif; ?>
			</div>
		</feather-slider>
		<?php
	}

	private function render_slide( array $slide, int $index, int $count, int $breakpoint ): void {
		$id = sanitize_html_class( $slide['_id'] ?? '' );
		$heading = trim( (string) ( $slide['heading'] ?? '' ) );
		$description = trim( (string) ( $slide['description'] ?? '' ) );
		$button = trim( (string) ( $slide['button_label'] ?? '' ) );
		$link = $slide['button_link'] ?? [];
		$url = esc_url( $link['url'] ?? '' );
		$external = ! empty( $link['is_external'] );
		$rel = trim( ( $external ? 'noopener noreferrer ' : '' ) . ( ! empty( $link['nofollow'] ) ? 'nofollow' : '' ) );
		/* translators: 1: Current slide number, 2: Total number of slides. */
		$label = sprintf( __( '%1$d of %2$d', 'feather-slider' ), $index + 1, $count );
		?>
		<div class="fs-slide elementor-repeater-item-<?php echo esc_attr( $id ); ?>"
			role="group" aria-roledescription="<?php esc_attr_e( 'slide', 'feather-slider' ); ?>"
			aria-label="<?php echo esc_attr( $label ); ?>"
			<?php echo $index ? 'hidden inert aria-hidden="true"' : ''; ?>>
			<?php $this->render_image( $slide['desktop_image'] ?? [], $slide['mobile_image'] ?? [], $breakpoint, 0 === $index ); ?>
			<?php if ( '' !== $heading || '' !== $description || ( '' !== $button && $url ) ) : ?>
				<div class="fs-layer">
					<div class="fs-panel" dir="<?php echo is_rtl() ? 'rtl' : 'ltr'; ?>">
						<div class="fs-panel-content">
							<?php if ( '' !== $heading ) : ?>
								<h2 class="fs-heading"><?php echo esc_html( $heading ); ?></h2>
							<?php endif; ?>
							<?php if ( '' !== $description ) : ?>
								<p class="fs-description"><?php echo nl2br( esc_html( $description ), false ); ?></p>
							<?php endif; ?>
							<?php if ( '' !== $button && $url ) : ?>
								<a class="fs-button" href="<?php echo $url; ?>"
									<?php echo $external ? 'target="_blank"' : ''; ?>
									<?php if ( $rel ) : ?>rel="<?php echo esc_attr( $rel ); ?>"<?php endif; ?>><?php echo esc_html( $button ); ?></a>
							<?php endif; ?>
						</div>
					</div>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	private function render_image( array $desktop, array $mobile, int $breakpoint, bool $first ): void {
		$desktop_id = absint( $desktop['id'] ?? 0 );
		$mobile_id = absint( $mobile['id'] ?? 0 );
		$desktop_url = ( $desktop_id ? wp_get_attachment_image_url( $desktop_id, 'full' ) : '' ) ?: ( $desktop['url'] ?? '' );
		$mobile_url = ( $mobile_id ? wp_get_attachment_image_url( $mobile_id, 'full' ) : '' ) ?: ( $mobile['url'] ?? '' );
		$attributes = [
			'class'         => 'fs-image',
			'loading'       => $first ? 'eager' : 'lazy',
			'decoding'      => 'async',
			'fetchpriority' => $first ? 'high' : 'auto',
			'sizes'         => '100vw',
		];
		$fallback_id = $desktop_url ? $desktop_id : $mobile_id;
		$fallback_url = $desktop_url ?: $mobile_url;
		if ( ! $fallback_url ) {
			return;
		}
		$img = $fallback_id ? wp_get_attachment_image( $fallback_id, 'full', false, $attributes ) : '';
		echo '<picture class="fs-picture">';
		if ( $desktop_url && $mobile_url && $desktop_url !== $mobile_url ) {
			$srcset = ( $mobile_id ? wp_get_attachment_image_srcset( $mobile_id, 'full' ) : '' ) ?: esc_url_raw( $mobile_url );
			printf( '<source media="(max-width: %dpx)" srcset="%s" sizes="100vw">', $breakpoint, esc_attr( $srcset ) );
		}
		if ( $img ) {
			echo $img; // WordPress constructs and escapes attachment markup.
		} else {
			printf(
				'<img class="fs-image" src="%s" alt="" loading="%s" decoding="async" fetchpriority="%s">',
				esc_url( $fallback_url ),
				esc_attr( $attributes['loading'] ),
				esc_attr( $attributes['fetchpriority'] )
			);
		}
		echo '</picture>';
	}

	private function enabled( array $settings, string $name, bool $default = true ): bool {
		return ( $settings[ $name ] ?? ( $default ? 'yes' : '' ) ) === 'yes';
	}

	private function option( $value, array $allowed, string $default ): string {
		return in_array( $value, $allowed, true ) ? $value : $default;
	}

	private function bounded_number( $value, int $default, int $min, int $max ): int {
		return is_numeric( $value ) ? max( $min, min( $max, (int) $value ) ) : $default;
	}
}
