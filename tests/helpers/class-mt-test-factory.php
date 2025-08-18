<?php
/**
 * Test Data Factory
 *
 * @package MobilityTrailblazers\Tests
 */

namespace MobilityTrailblazers\Tests;

/**
 * Factory for creating test data
 */
class MT_Test_Factory {

    /**
     * Generate random candidate data
     *
     * @param array $overrides Override specific fields
     * @return array Candidate data
     */
    public static function candidate($overrides = []) {
        $defaults = [
            'post_title' => 'Test Candidate ' . wp_rand(1, 1000),
            'post_type' => 'mt_candidate',
            'post_status' => 'publish',
            'post_content' => self::lorem_ipsum(),
            'meta_input' => [
                'mt_organization' => self::company_name(),
                'mt_position' => self::job_title(),
                'mt_linkedin_url' => 'https://linkedin.com/in/' . self::username(),
                'mt_website_url' => 'https://' . self::domain(),
                'mt_biography' => self::lorem_ipsum(3),
                'mt_innovation_summary' => self::lorem_ipsum(2),
                'mt_category' => self::random_category()
            ]
        ];
        
        return array_replace_recursive($defaults, $overrides);
    }

    /**
     * Generate random jury member data
     *
     * @param array $overrides Override specific fields
     * @return array Jury member data
     */
    public static function jury_member($overrides = []) {
        $defaults = [
            'post_title' => self::full_name(),
            'post_type' => 'mt_jury_member',
            'post_status' => 'publish',
            'meta_input' => [
                'mt_organization' => self::company_name(),
                'mt_position' => self::job_title(),
                'mt_expertise' => self::expertise_area(),
                'mt_email' => self::email(),
                'mt_phone' => self::phone()
            ]
        ];
        
        return array_replace_recursive($defaults, $overrides);
    }

    /**
     * Generate random evaluation data
     *
     * @param array $overrides Override specific fields
     * @return array Evaluation data
     */
    public static function evaluation($overrides = []) {
        $defaults = [
            'courage_score' => wp_rand(60, 100),
            'innovation_score' => wp_rand(60, 100),
            'implementation_score' => wp_rand(60, 100),
            'relevance_score' => wp_rand(60, 100),
            'visibility_score' => wp_rand(60, 100),
            'comments' => self::lorem_ipsum(1),
            'status' => self::random_from(['draft', 'submitted']),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ];
        
        return array_merge($defaults, $overrides);
    }

    /**
     * Generate random assignment data
     *
     * @param array $overrides Override specific fields
     * @return array Assignment data
     */
    public static function assignment($overrides = []) {
        $defaults = [
            'status' => 'pending',
            'assigned_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
            'notes' => self::lorem_ipsum(1)
        ];
        
        return array_merge($defaults, $overrides);
    }

    /**
     * Generate full name
     *
     * @return string Full name
     */
    public static function full_name() {
        $first_names = ['John', 'Jane', 'Michael', 'Sarah', 'David', 'Emma', 'Robert', 'Lisa'];
        $last_names = ['Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis'];
        
        return self::random_from($first_names) . ' ' . self::random_from($last_names);
    }

    /**
     * Generate company name
     *
     * @return string Company name
     */
    public static function company_name() {
        $prefixes = ['Global', 'Future', 'Smart', 'Green', 'Digital', 'Urban', 'Next'];
        $suffixes = ['Mobility', 'Transport', 'Solutions', 'Systems', 'Technologies', 'Innovations'];
        
        return self::random_from($prefixes) . ' ' . self::random_from($suffixes) . ' GmbH';
    }

    /**
     * Generate job title
     *
     * @return string Job title
     */
    public static function job_title() {
        $titles = [
            'CEO',
            'CTO',
            'Head of Innovation',
            'Director of Mobility',
            'Product Manager',
            'Research Lead',
            'Strategy Director',
            'Innovation Manager'
        ];
        
        return self::random_from($titles);
    }

    /**
     * Generate expertise area
     *
     * @return string Expertise area
     */
    public static function expertise_area() {
        $areas = [
            'Electric Mobility',
            'Autonomous Vehicles',
            'Urban Planning',
            'Sustainable Transport',
            'Mobility as a Service',
            'Smart Cities',
            'Public Transport',
            'Shared Mobility'
        ];
        
        return self::random_from($areas);
    }

    /**
     * Generate random category
     *
     * @return string Category
     */
    public static function random_category() {
        $categories = [
            'innovation',
            'sustainability',
            'technology',
            'infrastructure',
            'policy'
        ];
        
        return self::random_from($categories);
    }

    /**
     * Generate email
     *
     * @return string Email
     */
    public static function email() {
        return self::username() . '@' . self::domain();
    }

    /**
     * Generate username
     *
     * @return string Username
     */
    public static function username() {
        return strtolower(str_replace(' ', '.', self::full_name()));
    }

    /**
     * Generate domain
     *
     * @return string Domain
     */
    public static function domain() {
        $domains = ['example.com', 'test.de', 'mobility.eu', 'transport.org'];
        return self::random_from($domains);
    }

    /**
     * Generate phone number
     *
     * @return string Phone number
     */
    public static function phone() {
        return '+49 ' . wp_rand(100, 999) . ' ' . wp_rand(10000, 99999);
    }

    /**
     * Generate lorem ipsum text
     *
     * @param int $paragraphs Number of paragraphs
     * @return string Lorem ipsum text
     */
    public static function lorem_ipsum($paragraphs = 1) {
        $lorem = "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.";
        
        $result = [];
        for ($i = 0; $i < $paragraphs; $i++) {
            $result[] = $lorem;
        }
        
        return implode("\n\n", $result);
    }

    /**
     * Get random item from array
     *
     * @param array $array Source array
     * @return mixed Random item
     */
    private static function random_from($array) {
        return $array[array_rand($array)];
    }

    /**
     * Create batch of candidates
     *
     * @param int $count Number to create
     * @return array Created post IDs
     */
    public static function create_candidates($count = 10) {
        $ids = [];
        
        for ($i = 0; $i < $count; $i++) {
            $data = self::candidate();
            $ids[] = wp_insert_post($data);
        }
        
        return $ids;
    }

    /**
     * Create batch of jury members
     *
     * @param int $count Number to create
     * @return array Created post IDs
     */
    public static function create_jury_members($count = 5) {
        $ids = [];
        
        for ($i = 0; $i < $count; $i++) {
            // Create user first
            $user_id = wp_create_user(
                'jury_' . $i,
                wp_generate_password(),
                self::email()
            );
            
            // Add capability
            $user = get_user_by('id', $user_id);
            $user->add_cap('mt_submit_evaluations');
            
            // Create jury member post
            $data = self::jury_member([
                'meta_input' => [
                    'mt_user_id' => $user_id
                ]
            ]);
            
            $ids[] = wp_insert_post($data);
        }
        
        return $ids;
    }

    /**
     * Create test CSV data
     *
     * @param string $type Type of CSV (candidates or jury)
     * @param int $rows Number of rows
     * @return array CSV data
     */
    public static function csv_data($type = 'candidates', $rows = 10) {
        $data = [];
        
        if ($type === 'candidates') {
            // Header row
            $data[] = [
                'Name',
                'Organization',
                'Position',
                'LinkedIn',
                'Website',
                'Biography',
                'Innovation Summary',
                'Category'
            ];
            
            // Data rows
            for ($i = 0; $i < $rows; $i++) {
                $candidate = self::candidate();
                $data[] = [
                    $candidate['post_title'],
                    $candidate['meta_input']['mt_organization'],
                    $candidate['meta_input']['mt_position'],
                    $candidate['meta_input']['mt_linkedin_url'],
                    $candidate['meta_input']['mt_website_url'],
                    strip_tags($candidate['meta_input']['mt_biography']),
                    strip_tags($candidate['meta_input']['mt_innovation_summary']),
                    $candidate['meta_input']['mt_category']
                ];
            }
        } else {
            // Header row
            $data[] = [
                'Name',
                'Email',
                'Organization',
                'Position',
                'Expertise',
                'Phone'
            ];
            
            // Data rows
            for ($i = 0; $i < $rows; $i++) {
                $jury = self::jury_member();
                $data[] = [
                    $jury['post_title'],
                    $jury['meta_input']['mt_email'],
                    $jury['meta_input']['mt_organization'],
                    $jury['meta_input']['mt_position'],
                    $jury['meta_input']['mt_expertise'],
                    $jury['meta_input']['mt_phone']
                ];
            }
        }
        
        return $data;
    }
}