<?php
/**
 * Plugin Name: LearnPress Custom Features
 * Description: Plugin thực hiện 3 yêu cầu: Thanh thông báo, Shortcode thống kê khóa học, và Đổi màu nút.
 * Version: 1.0
 * Author: Phạm Ngọc Nhi
 */

// Chặn truy cập trực tiếp để bảo mật
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
// ==========================================
// YÊU CẦU 1: THANH THÔNG BÁO (NOTIFICATION BAR)
// ==========================================
function lp_custom_notification_bar() {
    // Chỉ hiển thị thanh này ở trang chi tiết Khóa học (Course)
    if ( ! is_singular( 'lp_course' ) ) {
        return;
    }

    // Kiểm tra xem người dùng đã đăng nhập chưa
    if ( is_user_logged_in() ) {
        $current_user = wp_get_current_user();
        $user_name = $current_user->display_name; // Lấy Tên user
        $message = "Chào " . esc_html( $user_name ) . ", bạn đã sẵn sàng bắt đầu bài học hôm nay chưa? 🚀";
        $bg_color = "#4CAF50"; // Màu xanh lá cho người đã đăng nhập
    } else {
        $message = "Đăng nhập để lưu tiến độ học tập! ⚠️";
        $bg_color = "#FF9800"; // Màu cam cho người chưa đăng nhập
    }

    // In ra HTML và CSS cho thanh thông báo nổi trên cùng
    echo '
    <div style="position: fixed; top: 0; left: 0; width: 100%; background-color: ' . $bg_color . '; color: #fff; text-align: center; padding: 12px; z-index: 99999; font-weight: bold; font-family: sans-serif; box-shadow: 0 2px 4px rgba(0,0,0,0.2);">
        ' . $message . '
    </div>
    <style>
        /* Đẩy toàn bộ trang web xuống một chút để không bị thanh thông báo đè lên header */
        html, body { margin-top: 20px !important; }
    </style>
    ';
}
// Dùng hook wp_footer để đẩy HTML ra trang web
add_action( 'wp_footer', 'lp_custom_notification_bar' );

// ==========================================
// YÊU CẦU 2: SHORTCODE THỐNG KÊ KHÓA HỌC
// ==========================================
function lp_custom_course_info_shortcode( $atts ) {
    // 1. Kiểm tra LearnPress có hoạt động không
    if ( ! class_exists( 'LearnPress' ) ) {
        return '<p>Vui lòng cài đặt và kích hoạt LearnPress.</p>';
    }

    // 2. Nhận ID khóa học từ người dùng nhập vào shortcode
    $atts = shortcode_atts( array(
        'id' => 0,
    ), $atts, 'lp_course_info' );

    $course_id = intval( $atts['id'] );

    // Kiểm tra nếu không nhập ID
    if ( ! $course_id ) {
        return '<p style="color:red;">Vui lòng nhập ID khóa học. Ví dụ: [lp_course_info id="12"]</p>';
    }

    // Lấy đối tượng khóa học từ hệ thống
    $course = learn_press_get_course( $course_id );
    if ( ! $course ) {
        return '<p style="color:red;">Không tìm thấy khóa học nào có ID là ' . $course_id . '</p>';
    }

    // 3. Lấy thông tin số bài học và thời lượng
    $total_lessons = $course->count_items( 'lp_lesson' ); // Đếm số bài học
    $duration      = $course->get_duration(); // Lấy thời gian dự kiến

    // 4. Kiểm tra trạng thái của người dùng
    $status_text = '<span style="color:gray; font-weight:bold;">Chưa đăng ký</span>'; // Mặc định
    
    if ( is_user_logged_in() ) {
        $user = learn_press_get_current_user();
        if ( $user ) {
            $course_status = $user->get_course_status( $course_id );
            
            if ( $course_status == 'enrolled' ) {
                $status_text = '<span style="color:#2196F3; font-weight:bold;">Đang học (Đã ghi danh)</span>';
            } elseif ( $course_status == 'completed' ) {
                $status_text = '<span style="color:#4CAF50; font-weight:bold;">✅ Đã hoàn thành</span>';
            }
        }
    } else {
        $status_text = '<span style="color:#FF9800;">Vui lòng đăng nhập để xem trạng thái</span>';
    }

    // 5. In ra giao diện HTML
    $html = '
    <div style="border: 2px dashed #00BCD4; padding: 15px; margin: 20px 0; border-radius: 8px; background: #F8FFFF; font-family: sans-serif;">
        <h3 style="margin-top:0; color: #009688;">📊 Thông tin chi tiết khóa học</h3>
        <ul style="list-style: none; padding: 0; font-size: 16px; line-height: 1.8;">
            <li>📖 <strong>Số lượng bài học:</strong> ' . esc_html( $total_lessons ) . ' bài</li>
            <li>⏱️ <strong>Thời gian dự kiến:</strong> ' . esc_html( $duration ) . '</li>
            <li>👤 <strong>Trạng thái của bạn:</strong> ' . $status_text . '</li>
        </ul>
    </div>';

    return $html;
}
add_shortcode( 'lp_course_info', 'lp_custom_course_info_shortcode' );

// ==========================================
// YÊU CẦU 3: TÙY BIẾN STYLE (CUSTOM CSS)
// ==========================================
function lp_custom_button_colors() {
    // Chỉ chèn mã CSS này khi người dùng đang ở trang chi tiết Khóa học
    if ( ! is_singular( 'lp_course' ) ) {
        return;
    }

    // In ra mã CSS đổi nút sang màu Cam (thương hiệu)
    echo '
    <style>
        /* Nhắm mục tiêu vào các nút bấm của LearnPress (Enroll, Finish, v.v.) */
        .learn-press .lp-button,
        form.enroll-course .lp-button,
        .lp-btn-enroll,
        .button-finish-course {
            background-color: #FF5722 !important; /* Đổi nền sang màu Cam */
            border-color: #E64A19 !important;     /* Viền cam đậm hơn một chút */
            color: #FFFFFF !important;            /* Chữ màu trắng */
            border-radius: 8px !important;        /* Bo tròn góc cho đẹp */
            transition: all 0.3s ease !important;
        }
        
        /* Hiệu ứng khi rê chuột vào nút (Hover) */
        .learn-press .lp-button:hover,
        form.enroll-course .lp-button:hover,
        .lp-btn-enroll:hover,
        .button-finish-course:hover {
            background-color: #D84315 !important; /* Cam sậm hơn khi rê chuột */
        }
    </style>
    ';
}
// Dùng hook wp_head để chèn đoạn CSS này vào thẻ <head> của trang web
add_action( 'wp_head', 'lp_custom_button_colors' );