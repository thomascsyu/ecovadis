<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * ISO42K_Email
 * Handles email sending for the EcoVadis Self Assessment
 * 
 * @version 2.0.0
 * 
 * Note: This class works with AI-generated content from ISO42K_AI class.
 * The AI class generates structured content in 7 sections: Key Insights, Overview (gap analysis), 
 * Current State, Risk Implications, Top Gaps, Recommendations, and Quick Win Actions.
 * This class parses and formats that content for email delivery.
 */
class ISO42K_Email {

    /**
     * Send user email with results
     */
    public static function send_user($lead, $percent, $maturity, $ai_summary = '', $pdf_path = null) {
        $email_settings = (array) get_option('iso42k_email_settings', []);
        
        $to = $lead['email'];
        $subject = 'Your EcoVadis Sustainability Assessment Results';
        
        // Build email content
        $body = self::build_user_email_content($lead, $percent, $maturity, $ai_summary, $pdf_path, $email_settings);
        
        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . self::get_from_name($email_settings) . ' <' . self::get_from_email($email_settings) . '>'
        ];
        
        // Performance optimization: add error handling for wp_mail
        $start_time = microtime(true);
        $result = wp_mail($to, $subject, $body, $headers);
        $duration_ms = round((microtime(true) - $start_time) * 1000);
        
        // Log result
        if ($result) {
            ISO42K_Logger::log('📧 User email sent to: ' . $to . ' (' . $duration_ms . 'ms)');
        } else {
            ISO42K_Logger::log('❌ User email failed to send to: ' . $to . ' (' . $duration_ms . 'ms)');
        }
        
        return $result;
    }

    /**
     * Send admin notification
     */
    public static function send_admin($lead, $percent, $maturity, $ai_summary = '', $pdf_path = null) {
        $email_settings = (array) get_option('iso42k_email_settings', []);
        
        // Check if admin notifications are enabled
        $admin_notification_enabled = !isset($email_settings['admin_notification_enabled']) || !empty($email_settings['admin_notification_enabled']);
        if (!$admin_notification_enabled) {
            return false; // Don't send if disabled
        }
        
        // Get admin email recipients
        $admin_emails = $email_settings['admin_notification_emails'] ?? get_option('admin_email');
        
        // Support both comma and semicolon separated emails
        $admin_emails = array_filter(array_map('trim', preg_split('/[,;]/', $admin_emails)));
        
        if (empty($admin_emails)) {
            return false;
        }
        
        $subject = 'New EcoVadis Sustainability Assessment Lead';
        
        // Build email content
        $body = self::build_admin_email_content($lead, $percent, $maturity, $ai_summary, $email_settings);
        
        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . self::get_from_name($email_settings) . ' <' . self::get_from_email($email_settings) . '>'
        ];
        
        $sent_count = 0;
        $failed_emails = [];
        
        // Performance optimization: Prepare the email body once and send to all recipients
        $start_time = microtime(true);
        foreach ($admin_emails as $admin_email) {
            if (is_email($admin_email)) {
                $result = wp_mail($admin_email, $subject, $body, $headers);
                if ($result) {
                    $sent_count++;
                    ISO42K_Logger::log('📧 Admin email sent to: ' . $admin_email);
                } else {
                    $failed_emails[] = $admin_email;
                    ISO42K_Logger::log('❌ Admin email failed to send to: ' . $admin_email);
                }
            } else {
                ISO42K_Logger::log('⚠️ Invalid admin email format: ' . $admin_email);
            }
        }
        $duration_ms = round((microtime(true) - $start_time) * 1000);
        
        // Log summary if there were failures
        if (!empty($failed_emails)) {
            ISO42K_Logger::log('⚠️ Failed to send admin emails to: ' . implode(', ', $failed_emails) . ' (' . $duration_ms . 'ms)');
        } else {
            ISO42K_Logger::log('✅ All admin emails sent successfully (' . $duration_ms . 'ms)');
        }
        
        return $sent_count > 0;
    }

    /**
     * Build user email content with modern professional layout
     */
    private static function build_user_email_content($lead, $percent, $maturity, $ai_summary, $pdf_path, $email_settings) {
        $logo_url = esc_url($email_settings['company_logo_url'] ?? '');
        $primary_color = esc_attr($email_settings['primary_color'] ?? '#2563eb');
        $secondary_color = esc_attr($email_settings['secondary_color'] ?? '#1e40af');
        $meeting_scheduler_url = esc_url($email_settings['meeting_scheduler_url'] ?? '');
        $meeting_button_text = esc_html($email_settings['meeting_button_text'] ?? 'Book a Consultation');
        
        $content = '<!DOCTYPE html>';
        $content .= '<html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Your EcoVadis Sustainability Assessment Results</title></head><body>';
        $content .= '<div style="max-width:650px;margin:0 auto;font-family:-apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Oxygen, Ubuntu, Cantarell, \'Open Sans\', \'Helvetica Neue\', sans-serif;padding:20px;">';
        
        // Header with logo and improved styling
        if ($logo_url) {
            $content .= '<div style="text-align:center;margin-bottom:30px;"><img src="' . $logo_url . '" alt="Company Logo" style="max-height:60px;"></div>';
        }
        
        $content .= '<h1 style="color:#1f2937;text-align:center;margin-bottom:30px;font-weight:700;font-size:28px;">Your EcoVadis Sustainability Assessment Results</h1>';
        
        // Results summary with modern card design
        $content .= '<div style="background:linear-gradient(135deg, ' . $primary_color . ' 0%, ' . $secondary_color . ' 100%);color:white;padding:30px;text-align:center;border-radius:12px;margin-bottom:25px;box-shadow: 0 10px 25px rgba(37, 99, 235, 0.2);">';
        $content .= '<h2 style="margin:0;font-size:42px;font-weight:700;text-shadow:0 2px 4px rgba(0,0,0,0.2);">' . $percent . '%</h2>';
        $content .= '<p style="margin:15px 0 0;font-size:20px;font-weight:500;opacity:0.9;">' . $maturity . ' Maturity Level</p>';
        $content .= '<div style="margin-top:15px;font-size:14px;opacity:0.8;">Comprehensive Security Assessment</div>';
        $content .= '</div>';
        
        // Maturity explanation with improved styling
        $content .= '<div style="background:#f9fafb;padding:25px;border-radius:12px;margin-bottom:25px;box-shadow: 0 4px 6px rgba(0,0,0,0.05);border: 1px solid #e5e7eb;">';
        $content .= '<h3 style="margin-top:0;margin-bottom:15px;color:#1f2937;font-size:20px;font-weight:600;">What This Means For Your Organization</h3>';
        
        if ($maturity === 'Initial') {
            $content .= '<p style="margin:10px 0; line-height: 1.6;">Your organization has largely ad-hoc or incomplete controls. The priority is establishing baseline policies, ownership, and key technical controls.</p>';
        } elseif ($maturity === 'Managed') {
            $content .= '<p style="margin:10px 0; line-height: 1.6;">Your organization has a foundation, but implementation may be inconsistent. The priority is standardizing processes and improving coverage.</p>';
        } elseif ($maturity === 'Established') {
            $content .= '<p style="margin:10px 0; line-height: 1.6;">Your organization has controls broadly implemented and defined. The priority is strengthening monitoring, measurement, and continual improvement.</p>';
        } else { // Optimised
            $content .= '<p style="margin:10px 0; line-height: 1.6;">Your organization has mature controls that are measured and continuously improved. The priority is automation, metrics, and proactive threat management.</p>';
        }
        
        $content .= '<div style="margin-top:15px; padding:12px; background:#e0f2fe; border-radius:8px; border-left:4px solid ' . $primary_color . ';">';
        $content .= '<p style="margin:0; font-weight:500; color:#0369a1;"><span style="font-weight:700;">Next Steps:</span> Focus on ' . 
            ($maturity === 'Initial' ? 'establishing foundational security policies and controls' : 
             ($maturity === 'Managed' ? 'standardizing and consistently implementing security processes' : 
              ($maturity === 'Established' ? 'enhancing monitoring and continuous improvement processes' : 
               'advanced threat management and automation'))) . 
            ' to advance your maturity level.</p>';
        $content .= '</div>';
        
        $content .= '</div>';
        
        // AI Summary if available
        if (!empty($ai_summary)) {
            $content .= '<div style="margin-bottom:25px;">';
            $content .= '<h3 style="margin-top:0;margin-bottom:20px;color:#1f2937;font-size:22px;font-weight:600;text-align:center;padding-bottom:10px;border-bottom:2px solid ' . $primary_color . ';">';
            $content .= 'AI-Powered Security Analysis';
            $content .= '</h3>';
            
            // Parse and format the AI summary into structured sections
            $formatted_ai_content = self::format_ai_analysis($ai_summary, $maturity, $percent);
            $content .= $formatted_ai_content;
            
            $content .= '</div>';
        }
        
        // PDF download if available
        if (!empty($lead['pdf_url'])) {
            $content .= '<div style="text-align:center;margin:30px 0;">';
            $content .= '<a href="' . esc_url($lead['pdf_url']) . '" style="background:linear-gradient(135deg, ' . $primary_color . ' 0%, ' . $secondary_color . ' 100%);color:white;padding:16px 35px;text-decoration:none;border-radius:8px;font-weight:bold;display:inline-block;box-shadow: 0 4px 6px rgba(37, 99, 235, 0.3);transition: all 0.3s ease;margin:0 10px;text-transform:uppercase;font-size:15px;letter-spacing:0.5px;">';
            $content .= '📥 Download Full PDF Report';
            $content .= '</a>';
            if ($meeting_scheduler_url) {
                $content .= '<a href="' . $meeting_scheduler_url . '" style="background:linear-gradient(135deg, #6b7280 0%, #4b5563 100%);color:white;padding:16px 35px;text-decoration:none;border-radius:8px;font-weight:bold;display:inline-block;box-shadow: 0 4px 6px rgba(107, 114, 128, 0.3);transition: all 0.3s ease;margin:0 10px;text-transform:uppercase;font-size:15px;letter-spacing:0.5px;">';
                $content .= $meeting_button_text;
                $content .= '</a>';
            }
            $content .= '</div>';
        } elseif ($meeting_scheduler_url) {
            // Show only meeting button if no PDF
            $content .= '<div style="text-align:center;margin:40px 0 30px;">';
            $content .= '<a href="' . $meeting_scheduler_url . '" style="background:linear-gradient(135deg, ' . $primary_color . ' 0%, ' . $secondary_color . ' 100%);color:white;padding:16px 40px;text-decoration:none;border-radius:8px;font-weight:bold;display:inline-block;box-shadow: 0 4px 6px rgba(37, 99, 235, 0.3);transition: all 0.3s ease;text-transform:uppercase;font-size:16px;letter-spacing:0.5px;">';
            $content .= $meeting_button_text;
            $content .= '</a>';
            $content .= '</div>';
        }
        
        // Contact information with modern styling
        $content .= '<div style="margin-top:40px;padding:25px 0 0;border-top:1px solid #e5e7eb;text-align:center;color:#6b7280;font-size:14px;">';
        $content .= '<p style="margin-bottom:8px;">Questions? Contact us at <a href="mailto:' . esc_html($email_settings['from_email'] ?? get_option('admin_email')) . '" style="color:' . $primary_color . ';text-decoration:none;">' . esc_html($email_settings['from_email'] ?? get_option('admin_email')) . '</a></p>';
        $content .= '<p style="margin:0;">Need immediate assistance? <a href="' . ($meeting_scheduler_url ?: '#') . '" style="color:' . $primary_color . ';text-decoration:underline;">Schedule a consultation</a></p>';
        $content .= '</div>';
        
        // Footer with subtle branding
        $content .= '<div style="margin-top:30px;text-align:center;color:#9ca3af;font-size:12px;padding-top:15px;border-top:1px solid #e5e7eb;">';
        $content .= '<p style="margin:0;">Powered by EcoVadis Self Assessment Tool</p>';
        $content .= '<p style="margin:0;margin-top:5px;">© ' . date('Y') . ' ' . esc_html($email_settings['from_name'] ?? get_bloginfo('name')) . '. All rights reserved.</p>';
        $content .= '</div>';
        
        $content .= '</div>';
        $content .= '</body></html>';
        
        return $content;
    }

    /**
     * Format AI analysis into structured sections
     */
    private static function format_ai_analysis($ai_summary, $maturity, $percent) {
        $content = '';
        
        // Parse the AI summary into structured sections
        $sections = self::parse_ai_analysis($ai_summary);
        
        // Add key insights section
        if (!empty($sections['key_insights'])) {
            $content .= '<div style="margin-bottom:20px;">';
            $content .= '<h4 style="color:#1f2937;margin-bottom:10px;">Key Insights</h4>';
            $content .= '<p>' . nl2br(esc_html(trim($sections['key_insights']))) . '</p>';
            $content .= '</div>';
        } else {
            // Generate default key insights
            $content .= '<div style="margin-bottom:20px;">';
            $content .= '<h4 style="color:#1f2937;margin-bottom:10px;">Key Insights</h4>';
            $content .= '<p><strong>Your score places you in the ' . esc_html($maturity) . ' category.</strong></p>';
            
            $focus_areas = '';
            if ($maturity === 'Initial') {
                $focus_areas = 'Primary focus areas: policy development, technical controls, and process documentation.';
            } elseif ($maturity === 'Managed') {
                $focus_areas = 'Primary focus areas: standardizing processes and improving coverage.';
            } elseif ($maturity === 'Established') {
                $focus_areas = 'Primary focus areas: strengthening monitoring, measurement, and continual improvement.';
            } else { // Optimised
                $focus_areas = 'Primary focus areas: automation, metrics, and proactive threat management.';
            }
            
            $content .= '<p>' . esc_html($focus_areas) . '</p>';
            
            $timeline = 'Estimated implementation timeline: 12–18 months.';
            if ($percent >= 75) {
                $timeline = 'Estimated implementation timeline: 3–6 months.';
            } elseif ($percent >= 50) {
                $timeline = 'Estimated implementation timeline: 6–12 months.';
            } elseif ($percent >= 25) {
                $timeline = 'Estimated implementation timeline: 9–15 months.';
            }
            $content .= '<p>' . esc_html($timeline) . '</p>';
            $content .= '</div>';
        }
        
        // Add AI Analysis Overview section
        if (!empty($sections['gap_analysis'])) {
            $content .= '<div style="margin-bottom:20px;">';
            $content .= '<h4 style="color:#1f2937;margin-bottom:10px;">Overview</h4>';
            $content .= '<p>' . nl2br(esc_html(trim($sections['gap_analysis']))) . '</p>';
            $content .= '</div>';
        }
        
        // Add Current State section
        if (!empty($sections['current_state'])) {
            $content .= '<div style="margin-bottom:20px;">';
            $content .= '<h4 style="color:#1f2937;margin-bottom:10px;">Current State</h4>';
            $content .= '<p>' . nl2br(esc_html(trim($sections['current_state']))) . '</p>';
            $content .= '</div>';
        }
        
        // Add Risk Implications section
        if (!empty($sections['risk_implications'])) {
            $content .= '<div style="margin-bottom:20px;">';
            $content .= '<h4 style="color:#1f2937;margin-bottom:10px;">Risk Implications</h4>';
            $content .= '<p>' . nl2br(esc_html(trim($sections['risk_implications']))) . '</p>';
            $content .= '</div>';
        }
        
        // Add Top Gaps section (format as narrative)
        if (!empty($sections['top_gaps'])) {
            $content .= '<div style="margin-bottom:20px;">';
            $content .= '<h4 style="color:#1f2937;margin-bottom:10px;">Top Gaps</h4>';
            
            // Convert text to list items
            $top_gaps_text = trim($sections['top_gaps']);
            $gap_items = preg_split('/\n|\r\n?/', $top_gaps_text);
            $gap_items = array_filter(array_map('trim', $gap_items));
            
            if (!empty($gap_items)) {
                $content .= '<ul style="margin:10px 0;padding-left:20px;">';
                foreach ($gap_items as $item) {
                    // Remove bullet points or numbers if present
                    $item = preg_replace('/^[•\-\d\.\)\s]+/', '', $item);
                    if (!empty($item)) {
                        $content .= '<li style="margin-bottom:5px;">' . esc_html($item) . '</li>';
                    }
                }
                $content .= '</ul>';
            } else {
                $content .= '<p>' . nl2br(esc_html($top_gaps_text)) . '</p>';
            }
            
            $content .= '</div>';
        }
        
        // Add Recommendations section (format as list)
        if (!empty($sections['recommendations'])) {
            $content .= '<div style="margin-bottom:20px;">';
            $content .= '<h4 style="color:#1f2937;margin-bottom:10px;">Recommendations</h4>';
            
            $rec_text = trim($sections['recommendations']);
            $rec_items = preg_split('/\n|\r\n?/', $rec_text);
            $rec_items = array_filter(array_map('trim', $rec_items));
            
            if (!empty($rec_items)) {
                $content .= '<ul style="margin:10px 0;padding-left:20px;">';
                foreach ($rec_items as $item) {
                    $item = preg_replace('/^[•\-\d\.\)\s]+/', '', $item);
                    if (!empty($item)) {
                        $content .= '<li style="margin-bottom:5px;">' . esc_html($item) . '</li>';
                    }
                }
                $content .= '</ul>';
            } else {
                $content .= '<p>' . nl2br(esc_html($rec_text)) . '</p>';
            }
            
            $content .= '</div>';
        }
        
        // Add Quick Win Actions section (format as list)
        if (!empty($sections['quick_wins'])) {
            $content .= '<div style="margin-bottom:20px;">';
            $content .= '<h4 style="color:#1f2937;margin-bottom:10px;">Quick Win Actions</h4>';
            
            $quick_text = trim($sections['quick_wins']);
            $quick_items = preg_split('/\n|\r\n?/', $quick_text);
            $quick_items = array_filter(array_map('trim', $quick_items));
            
            if (!empty($quick_items)) {
                $content .= '<ul style="margin:10px 0;padding-left:20px;">';
                foreach ($quick_items as $item) {
                    $item = preg_replace('/^[•\-\d\.\)\s]+/', '', $item);
                    if (!empty($item)) {
                        $content .= '<li style="margin-bottom:5px;">' . esc_html($item) . '</li>';
                    }
                }
                $content .= '</ul>';
            } else {
                $content .= '<p>' . nl2br(esc_html($quick_text)) . '</p>';
            }
            
            $content .= '</div>';
        }
        
        // If no sections were found, just display the raw summary
        if (empty($sections['key_insights']) && empty($sections['gap_analysis']) && 
            empty($sections['current_state']) && empty($sections['risk_implications']) &&
            empty($sections['top_gaps']) && empty($sections['recommendations']) && 
            empty($sections['quick_wins'])) {
            $content .= '<div style="margin-bottom:20px;">';
            $content .= '<p style="margin:10px 0;">' . nl2br(esc_html($ai_summary)) . '</p>';
            $content .= '</div>';
        }
        
        return $content;
    }

    /**
     * Parse and structure AI analysis into required sections
     * 
     * @param string $ai_summary The raw AI analysis content
     * @return array Structured sections
     */
    private static function parse_ai_analysis($ai_summary) {
        $sections = [
            'key_insights' => '',
            'gap_analysis' => '',  // This is the "Overview" section in the AI-generated content,
            'current_state' => '',
            'risk_implications' => '',
            'top_gaps' => '',
            'recommendations' => '',
            'quick_wins' => ''
        ];
        
        $clean_summary = str_replace(["\r\n", "\r"], "\n", $ai_summary);
        
        // Try to parse based on section headers
        $section_patterns = [
            'key_insights' => '/(?:1\)\s*Key Insights|Key Insights)[\s\n]*([\s\S]*?)(?=\n\n?\s*(?:2\)|2\.|2\s|AI-Powered Gap Analysis|$))/i',
            'gap_analysis' => '/(?:2\)\s*AI-Powered Gap Analysis|AI-Powered Gap Analysis|Gap Analysis)[\s\n]*([\s\S]*?)(?=\n\n?\s*(?:3\)|3\.|3\s|Current State|$))/i',
            'current_state' => '/(?:3\)\s*Current State|Current State)[\s\n]*([\s\S]*?)(?=\n\n?\s*(?:4\)|4\.|4\s|Risk Implications|$))/i',
            'risk_implications' => '/(?:4\)\s*Risk Implications|Risk Implications)[\s\n]*([\s\S]*?)(?=\n\n?\s*(?:5\)|5\.|5\s|Top Gaps|$))/i',
            'top_gaps' => '/(?:5\)\s*Top Gaps|Top Gaps)[\s\n]*([\s\S]*?)(?=\n\n?\s*(?:6\)|6\.|6\s|Recommendations|$))/i',
            'recommendations' => '/(?:6\)\s*Recommendations|Recommendations)[\s\n]*([\s\S]*?)(?=\n\n?\s*(?:7\)|7\.|7\s|Quick Win Actions|$))/i',
            'quick_wins' => '/(?:7\)\s*Quick Win Actions|Quick Win Actions)[\s\n]*([\s\S]*?)$/i'
        ];
        
        foreach ($section_patterns as $key => $pattern) {
            if (preg_match($pattern, $clean_summary, $matches)) {
                $sections[$key] = trim($matches[1]);
            }
        }
        
        // If pattern matching didn't work well, try line-by-line parsing
        if (empty(array_filter($sections))) {
            $lines = explode("\n", $clean_summary);
            $current_section = '';
            $current_content = '';
            
            foreach ($lines as $line) {
                $line = trim($line);
                
                // Check for section headers
                if (preg_match('/^\d+\)\s*(.+)/', $line, $match)) {
                    // Save previous section
                    if ($current_section && $current_content) {
                        $sections[$current_section] = trim($current_content);
                        $current_content = '';
                    }
                    
                    $header = strtolower(trim($match[1]));
                    
                    // Map header to section key
                    if (strpos($header, 'key insight') !== false) {
                        $current_section = 'key_insights';
                    } elseif (strpos($header, 'ai-powered gap analysis') !== false || strpos($header, 'gap analysis') !== false || strpos($header, 'overview') !== false) {
                        $current_section = 'gap_analysis';
                    } elseif (strpos($header, 'current state') !== false) {
                        $current_section = 'current_state';
                    } elseif (strpos($header, 'risk implication') !== false) {
                        $current_section = 'risk_implications';
                    } elseif (strpos($header, 'top gap') !== false) {
                        $current_section = 'top_gaps';
                    } elseif (strpos($header, 'recommendation') !== false) {
                        $current_section = 'recommendations';
                    } elseif (strpos($header, 'quick win') !== false) {
                        $current_section = 'quick_wins';
                    } else {
                        $current_section = '';
                    }
                } elseif ($current_section) {
                    // Add content to current section
                    $current_content .= $line . "\n";
                }
            }
            
            // Save the last section
            if ($current_section && $current_content) {
                $sections[$current_section] = trim($current_content);
            }
        }
        
        return $sections;
    }

    /**
     * Build admin email content
     */
    private static function build_admin_email_content($lead, $percent, $maturity, $ai_summary, $email_settings) {
        $primary_color = esc_attr($email_settings['primary_color'] ?? '#2563eb');
        $secondary_color = esc_attr($email_settings['secondary_color'] ?? '#1e40af');
        
        $content = '<!DOCTYPE html>';
        $content .= '<html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>New Assessment Lead</title></head><body>';
        $content .= '<div style="max-width:650px;margin:0 auto;font-family:-apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Oxygen, Ubuntu, Cantarell, \'Open Sans\', \'Helvetica Neue\', sans-serif;padding:20px;">';
        
        $content .= '<h1 style="color:#1f2937;margin-bottom:30px;font-weight:700;font-size:28px;">New EcoVadis Sustainability Assessment Lead</h1>';
        
        // Lead info
        $content .= '<div style="background:#f9fafb;padding:25px;border-radius:12px;margin-bottom:25px;box-shadow: 0 4px 6px rgba(0,0,0,0.05);border: 1px solid #e5e7eb;">';
        $content .= '<h3 style="margin-top:0;margin-bottom:15px;color:#1f2937;font-size:20px;font-weight:600;">Contact Information</h3>';
        $content .= '<div style="margin-bottom:12px;"><strong style="display:inline-block;width:100px;color:#374151;">Name:</strong> <span style="color:#4b5563;">' . esc_html($lead['name']) . '</span></div>';
        $content .= '<div style="margin-bottom:12px;"><strong style="display:inline-block;width:100px;color:#374151;">Email:</strong> <span style="color:#4b5563;">' . esc_html($lead['email']) . '</span></div>';
        $content .= '<div style="margin-bottom:12px;"><strong style="display:inline-block;width:100px;color:#374151;">Phone:</strong> <span style="color:#4b5563;">' . esc_html($lead['phone']) . '</span></div>';
        $content .= '<div style="margin-bottom:12px;"><strong style="display:inline-block;width:100px;color:#374151;">Company:</strong> <span style="color:#4b5563;">' . esc_html($lead['company']) . '</span></div>';
        $content .= '<div style="margin-bottom:12px;"><strong style="display:inline-block;width:100px;color:#374151;">Staff Size:</strong> <span style="color:#4b5563;">' . esc_html($lead['staff']) . '</span></div>';
        $content .= '</div>';
        
        // Results summary
        $content .= '<div style="background:linear-gradient(135deg, ' . $primary_color . ' 0%, ' . $secondary_color . ' 100%);color:white;padding:25px;text-align:center;border-radius:12px;margin-bottom:25px;box-shadow: 0 10px 25px rgba(37, 99, 235, 0.2);">';
        $content .= '<h2 style="margin:0;font-size:36px;font-weight:700;text-shadow:0 2px 4px rgba(0,0,0,0.2);">Score: ' . $percent . '%</h2>';
        $content .= '<p style="margin:12px 0 0;font-size:20px;font-weight:500;opacity:0.9;">Maturity: ' . $maturity . '</p>';
        $content .= '<div style="margin-top:12px;font-size:14px;opacity:0.8;">Comprehensive Security Assessment</div>';
        $content .= '</div>';
        
        // AI Summary if available
        if (!empty($ai_summary)) {
            $content .= '<div style="margin-bottom:25px;">';
            $content .= '<h3 style="margin-top:0;margin-bottom:20px;color:#1f2937;font-size:22px;font-weight:600;text-align:center;padding-bottom:10px;border-bottom:2px solid ' . $primary_color . ';">';
            $content .= 'AI-Powered Security Analysis';
            $content .= '</h3>';
            
            // Parse and format the AI summary into structured sections
            $formatted_ai_content = self::format_ai_analysis($ai_summary, $maturity, $percent);
            $content .= $formatted_ai_content;
            
            $content .= '</div>';
        }
        
        // Action buttons for admin
        $content .= '<div style="text-align:center;margin-top:30px;">';
        $content .= '<a href="mailto:' . esc_html($lead['email']) . '" style="background:linear-gradient(135deg, ' . $primary_color . ' 0%, ' . $secondary_color . ' 100%);color:white;padding:14px 30px;text-decoration:none;border-radius:8px;font-weight:bold;display:inline-block;box-shadow: 0 4px 6px rgba(37, 99, 235, 0.3);transition: all 0.3s ease;margin:0 10px;text-transform:uppercase;font-size:15px;letter-spacing:0.5px;">';
        $content .= '📧 Email Lead';
        $content .= '</a>';
        $content .= '<a href="tel:' . esc_html($lead['phone']) . '" style="background:linear-gradient(135deg, #10b981 0%, #059669 100%);color:white;padding:14px 30px;text-decoration:none;border-radius:8px;font-weight:bold;display:inline-block;box-shadow: 0 4px 6px rgba(16, 185, 129, 0.3);transition: all 0.3s ease;margin:0 10px;text-transform:uppercase;font-size:15px;letter-spacing:0.5px;">';
        $content .= '📞 Call Lead';
        $content .= '</a>';
        $content .= '</div>';
        
        $content .= '</div>';
        $content .= '</body></html>';
        
        return $content;
    }

    /**
     * Get from name for emails
     */
    private static function get_from_name($email_settings) {
        return $email_settings['from_name'] ?? get_bloginfo('name');
    }

    /**
     * Get from email for emails
     */
    private static function get_from_email($email_settings) {
        return $email_settings['from_email'] ?? get_option('admin_email');
    }

    /**
     * Test email configuration
     */
    public static function test_config($test_email) {
        if (!is_email($test_email)) {
            return [
                'success' => false,
                'message' => 'Invalid email address'
            ];
        }

        $email_settings = (array) get_option('iso42k_email_settings', []);
        
        $subject = 'EcoVadis Assessment - Test Email';
        $body = 'This is a test email from your EcoVadis Self Assessment plugin. Email configuration is working correctly!';
        
        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . self::get_from_name($email_settings) . ' <' . self::get_from_email($email_settings) . '>'
        ];
        
        $result = wp_mail($test_email, $subject, $body, $headers);
        
        if ($result) {
            return [
                'success' => true,
                'message' => 'Test email sent successfully to ' . $test_email
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Test email failed to send. Please check your server\'s mail configuration.'
            ];
        }
    }

    /**
     * Parse email list (helper method)
     */
    private static function parse_email_list($emails) {
        if (is_array($emails)) {
            return $emails;
        }
        
        $email_list = array_filter(array_map('trim', preg_split('/[,;]/', $emails)));
        return array_filter($email_list, 'is_email');
    }

    /**
     * Create admin notification message (helper method)
     */
    private static function create_admin_notification_message($user_name, $user_email, $results) {
        return "New assessment completed by: $user_name ($user_email)\n\nResults: " . json_encode($results, JSON_PRETTY_PRINT);
    }

    /**
     * Test admin email configuration
     */
    public static function test_admin_notification() {
        $email_settings = (array) get_option('iso42k_email_settings', []);
        
        // Check if admin notifications are enabled
        $admin_notification_enabled = !isset($email_settings['admin_notification_enabled']) || !empty($email_settings['admin_notification_enabled']);
        if (!$admin_notification_enabled) {
            return [
                'success' => false,
                'message' => 'Admin notifications are not enabled in settings'
            ];
        }
        
        // Get admin email recipients
        $admin_emails = $email_settings['admin_notification_emails'] ?? get_option('admin_email');
        
        // Support both comma and semicolon separated emails
        $admin_emails = array_filter(array_map('trim', preg_split('/[,;]/', $admin_emails)));
        
        if (empty($admin_emails)) {
            return [
                'success' => false,
                'message' => 'No admin email addresses configured'
            ];
        }

        $subject = 'EcoVadis Assessment - Test Admin Notification';
        $body = 'This is a test admin notification from your EcoVadis Self Assessment plugin. Admin email configuration is working correctly!';
        
        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . self::get_from_name($email_settings) . ' <' . self::get_from_email($email_settings) . '>'
        ];
        
        $sent_count = 0;
        $failed_emails = [];
        
        foreach ($admin_emails as $admin_email) {
            if (is_email($admin_email)) {
                $result = wp_mail($admin_email, $subject, $body, $headers);
                if ($result) {
                    $sent_count++;
                    ISO42K_Logger::log('📧 Test admin email sent to: ' . $admin_email);
                } else {
                    $failed_emails[] = $admin_email;
                    ISO42K_Logger::log('❌ Test admin email failed to send to: ' . $admin_email);
                }
            } else {
                ISO42K_Logger::log('⚠️ Invalid admin email format: ' . $admin_email);
            }
        }
        
        if ($sent_count > 0) {
            return [
                'success' => true,
                'message' => "Test admin email sent successfully to $sent_count recipient(s)"
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Test admin email failed to send to all recipients. Please check your server\'s mail configuration and email settings.'
            ];
        }
    }
}