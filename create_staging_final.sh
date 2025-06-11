#!/bin/bash

# Mobility Trailblazers - Dummy Data Generation for STAGING
# This script targets the STAGING WordPress container: mobility_wordpress_STAGING

CONTAINER_NAME="mobility_wordpress_STAGING"

echo "=== Mobility Trailblazers STAGING Data Setup ==="
echo "Target container: $CONTAINER_NAME"
echo ""

# Check if the container is running
echo "Checking if staging container is running..."
if ! docker ps | grep -q "$CONTAINER_NAME"; then
    echo "❌ ERROR: $CONTAINER_NAME container is not running!"
    echo "Please start your staging stack first."
    exit 1
fi

echo "✅ Staging container is running"
echo ""

# Check if WP-CLI is available in the WordPress container
echo "Checking WP-CLI availability..."
if ! docker exec $CONTAINER_NAME which wp > /dev/null 2>&1; then
    echo "❌ WP-CLI not found in container. Installing..."
    docker exec $CONTAINER_NAME bash -c "
        curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
        chmod +x wp-cli.phar
        mv wp-cli.phar /usr/local/bin/wp
    "
    echo "✅ WP-CLI installed"
else
    echo "✅ WP-CLI is available"
fi

# Set WP-CLI command with proper path and allow root
WP_CLI_CMD="docker exec $CONTAINER_NAME wp --path=/var/www/html --allow-root"

echo ""
echo "=== Creating 20 Dummy Jury Members ==="

# Function to create user and add meta data
create_jury_member() {
    local username=$1
    local email=$2
    local first_name=$3
    local last_name=$4
    local display_name=$5
    local company=$6
    local position=$7
    local expertise=$8
    local bio=$9
    local linkedin=${10}
    local location=${11}
    
    # Create the user
    $WP_CLI_CMD user create "$username" "$email" \
        --role=editor \
        --first_name="$first_name" \
        --last_name="$last_name" \
        --display_name="$display_name" \
        --user_pass="JuryPass2025!"
    
    # Add custom meta fields
    $WP_CLI_CMD user meta add "$username" "company" "$company"
    $WP_CLI_CMD user meta add "$username" "position" "$position"
    $WP_CLI_CMD user meta add "$username" "expertise" "$expertise"
    $WP_CLI_CMD user meta add "$username" "bio" "$bio"
    $WP_CLI_CMD user meta add "$username" "linkedin" "$linkedin"
    $WP_CLI_CMD user meta add "$username" "location" "$location"
    $WP_CLI_CMD user meta add "$username" "user_type" "jury_member"
}

# Function to create candidate
create_candidate() {
    local username=$1
    local email=$2
    local first_name=$3
    local last_name=$4
    local display_name=$5
    local company=$6
    local position=$7
    local category=$8
    local expertise=$9
    local achievement=${10}
    local bio=${11}
    local linkedin=${12}
    local location=${13}
    
    # Create the user
    $WP_CLI_CMD user create "$username" "$email" \
        --role=subscriber \
        --first_name="$first_name" \
        --last_name="$last_name" \
        --display_name="$display_name" \
        --user_pass="CandPass2025!"
    
    # Add custom meta fields
    $WP_CLI_CMD user meta add "$username" "company" "$company"
    $WP_CLI_CMD user meta add "$username" "position" "$position"
    $WP_CLI_CMD user meta add "$username" "category" "$category"
    $WP_CLI_CMD user meta add "$username" "expertise" "$expertise"
    $WP_CLI_CMD user meta add "$username" "achievement" "$achievement"
    $WP_CLI_CMD user meta add "$username" "bio" "$bio"
    $WP_CLI_CMD user meta add "$username" "linkedin" "$linkedin"
    $WP_CLI_CMD user meta add "$username" "location" "$location"
    $WP_CLI_CMD user meta add "$username" "user_type" "candidate"
    
    # Add scoring fields (empty for now)
    $WP_CLI_CMD user meta add "$username" "innovation_score" ""
    $WP_CLI_CMD user meta add "$username" "implementation_score" ""
    $WP_CLI_CMD user meta add "$username" "role_model_score" ""
    $WP_CLI_CMD user meta add "$username" "courage_score" ""
}

# Create Jury Members
echo "Creating detailed jury members..."

create_jury_member "jury1" "dr.mueller@automotive-tech.de" "Dr. Andreas" "Müller" "Dr. Andreas Müller" \
    "Volkswagen AG" "Head of Electric Mobility" "Automotive & E-Mobility" \
    "20+ years in automotive industry, leading VW electric transformation" \
    "https://linkedin.com/in/andreas-mueller-vw" "Wolfsburg, Germany"

create_jury_member "jury2" "s.schneider@db-mobility.de" "Sabine" "Schneider" "Sabine Schneider" \
    "Deutsche Bahn AG" "Director Innovation & Digital" "Rail & Public Transport" \
    "Expert in digitalization of public transport systems" \
    "https://linkedin.com/in/sabine-schneider-db" "Berlin, Germany"

create_jury_member "jury3" "m.weber@lufthansa-group.com" "Michael" "Weber" "Michael Weber" \
    "Lufthansa Group" "VP Sustainable Aviation" "Aviation & Sustainability" \
    "Leading sustainable aviation initiatives at Lufthansa" \
    "https://linkedin.com/in/michael-weber-lufthansa" "Frankfurt, Germany"

create_jury_member "jury4" "j.fischer@mobility-vc.com" "Julia" "Fischer" "Julia Fischer" \
    "Mobility Ventures" "Managing Partner" "Venture Capital & Startups" \
    "Investor focused on mobility and transportation startups" \
    "https://linkedin.com/in/julia-fischer-vc" "Munich, Germany"

create_jury_member "jury5" "t.bauer@bmvi.bund.de" "Thomas" "Bauer" "Thomas Bauer" \
    "German Federal Ministry of Transport" "State Secretary" "Transport Policy" \
    "Senior government official shaping national transport policy" \
    "https://linkedin.com/in/thomas-bauer-bmvi" "Berlin, Germany"

create_jury_member "jury6" "prof.lang@eth.ch" "Prof. Dr. Anna" "Lang" "Prof. Dr. Anna Lang" \
    "ETH Zurich" "Professor of Mobility Systems" "Research & Innovation" \
    "Leading researcher in smart mobility and urban transport" \
    "https://linkedin.com/in/anna-lang-eth" "Zurich, Switzerland"

create_jury_member "jury7" "r.hoffmann@bosch.com" "Robert" "Hoffmann" "Robert Hoffmann" \
    "Robert Bosch GmbH" "Executive VP Mobility Solutions" "Automotive Technology" \
    "Expert in connected and autonomous vehicle technologies" \
    "https://linkedin.com/in/robert-hoffmann-bosch" "Stuttgart, Germany"

create_jury_member "jury8" "c.klein@dhl.com" "Christine" "Klein" "Christine Klein" \
    "DHL Group" "Chief Innovation Officer" "Logistics & Supply Chain" \
    "Driving innovation in logistics and last-mile delivery" \
    "https://linkedin.com/in/christine-klein-dhl" "Bonn, Germany"

create_jury_member "jury9" "h.richter@eon.com" "Hans" "Richter" "Hans Richter" \
    "E.ON SE" "Head of E-Mobility" "Energy & Charging Infrastructure" \
    "Leading charging infrastructure development across Europe" \
    "https://linkedin.com/in/hans-richter-eon" "Essen, Germany"

create_jury_member "jury10" "m.keller@sbb.ch" "Martin" "Keller" "Martin Keller" \
    "Swiss Federal Railways (SBB)" "Head of Digital Transformation" "Public Transport Innovation" \
    "Digitizing Swiss public transport systems" \
    "https://linkedin.com/in/martin-keller-sbb" "Bern, Switzerland"

# Create remaining jury members (11-20) with simpler data
echo "Creating remaining jury members..."
for i in {11..20}; do
    create_jury_member "jury$i" "jury$i@mobility-expert.com" "Jury" "Member$i" "Jury Member $i" \
        "Mobility Expert Corp $i" "Senior Expert" "Mobility Innovation" \
        "Expert jury member for mobility transformation" \
        "https://linkedin.com/in/jury$i" "DACH Region"
done

echo "=== Jury Members Created Successfully ==="
echo ""
echo "=== Creating 150 Dummy Candidates ==="

# Create featured candidates with detailed info
echo "Creating featured established company candidates..."

create_candidate "candidate1" "m.hartmann@mercedes.com" "Dr. Marcus" "Hartmann" "Dr. Marcus Hartmann" \
    "Mercedes-Benz Group" "Chief Technology Officer" "established" "Autonomous Driving" \
    "Led development of Level 3 autonomous driving system" \
    "Pioneering autonomous vehicle technology at Mercedes-Benz" \
    "https://linkedin.com/in/marcus-hartmann-mb" "Stuttgart, Germany"

create_candidate "candidate2" "s.lehmann@audi.com" "Sandra" "Lehmann" "Sandra Lehmann" \
    "AUDI AG" "Head of Electric Vehicle Strategy" "established" "Electric Mobility" \
    "Launched Audi e-tron family with 500km range" \
    "Driving Audi electric transformation strategy" \
    "https://linkedin.com/in/sandra-lehmann-audi" "Ingolstadt, Germany"

create_candidate "candidate3" "j.koch@porsche.com" "Jürgen" "Koch" "Jürgen Koch" \
    "Porsche AG" "Director Synthetic Fuels" "established" "Alternative Fuels" \
    "Developed breakthrough synthetic fuel production process" \
    "Leading sustainable fuel innovation at Porsche" \
    "https://linkedin.com/in/juergen-koch-porsche" "Stuttgart, Germany"

# Create remaining established company candidates (4-50)
echo "Creating remaining established company candidates..."
for i in {4..50}; do
    create_candidate "candidate$i" "candidate$i@established.com" "Established" "Expert$i" "Established Expert $i" \
        "Established Corp $i" "Innovation Lead" "established" "Mobility Tech" \
        "Major innovation in mobility sector" \
        "Innovation leader at established company" \
        "https://linkedin.com/in/candidate$i" "Germany"
done

# STARTUP CATEGORY (51-100)
echo "Creating startup candidates..."

create_candidate "candidate51" "l.mueller@tier.app" "Lisa" "Müller" "Lisa Müller" \
    "TIER Mobility" "Co-Founder & CEO" "startups" "Micro-Mobility" \
    "Scaled e-scooter sharing to 200+ cities" \
    "Co-founded leading European micro-mobility platform" \
    "https://linkedin.com/in/lisa-mueller-tier" "Berlin, Germany"

create_candidate "candidate52" "m.schmidt@starship.xyz" "Max" "Schmidt" "Max Schmidt" \
    "Starship Technologies" "Founder" "startups" "Autonomous Delivery" \
    "Deployed 2000+ delivery robots across Europe" \
    "Pioneer in autonomous last-mile delivery robots" \
    "https://linkedin.com/in/max-schmidt-starship" "Hamburg, Germany"

# Create remaining startup candidates (53-100)
for i in {53..100}; do
    create_candidate "candidate$i" "candidate$i@startup.com" "Startup" "Founder$i" "Startup Founder $i" \
        "Startup $i" "Founder/CEO" "startups" "Mobility Innovation" \
        "Breakthrough innovation in mobility" \
        "Innovative startup founder transforming mobility" \
        "https://linkedin.com/in/founder$i" "DACH Region"
done

# POLITICS & PUBLIC COMPANIES CATEGORY (101-150)
echo "Creating politics & public sector candidates..."

create_candidate "candidate101" "h.baerbock@bundestag.de" "Dr. Helena" "Baerbock" "Dr. Helena Baerbock" \
    "German Federal Government" "State Secretary for Transport" "politics" "Transport Policy" \
    "Implemented €10B climate-friendly transport package" \
    "Leading national transport transformation policy" \
    "https://linkedin.com/in/helena-baerbock" "Berlin, Germany"

create_candidate "candidate102" "t.reiter@muenchen.de" "Thomas" "Reiter" "Thomas Reiter" \
    "City of Munich" "Mayor" "politics" "Urban Mobility" \
    "Launched largest car-free city center in Europe" \
    "Transforming Munich into sustainable mobility city" \
    "https://linkedin.com/in/thomas-reiter-munich" "Munich, Germany"

# Create remaining politics/public candidates (103-150)
for i in {103..150}; do
    create_candidate "candidate$i" "candidate$i@public.gov" "Public" "Official$i" "Public Official $i" \
        "Public Agency $i" "Director" "politics" "Public Policy" \
        "Major policy innovation in mobility" \
        "Public sector mobility innovation leader" \
        "https://linkedin.com/in/official$i" "DACH Region"
done

echo ""
echo "=== Data Creation Complete! ==="
echo ""
echo "📊 Summary:"
echo "✅ 20 Jury Members created (role: editor)"
echo "✅ 150 Candidates created (role: subscriber)"
echo "   - 50 Established Companies (candidates 1-50)"
echo "   - 50 Startups (candidates 51-100)"  
echo "   - 50 Politics & Public (candidates 101-150)"
echo ""
echo "🔐 Default Passwords:"
echo "   Jury Members: JuryPass2025!"
echo "   Candidates: CandPass2025!"
echo ""
echo "🌐 Access your staging site:"
echo "   http://192.168.1.7:9989"
echo ""
echo "📝 Next Steps:"
echo "1. Login to WordPress admin"
echo "2. Install plugins for custom user management"
echo "3. Create voting interface for jury members"
echo "4. Configure candidate profile displays"
echo ""
echo "🔧 Useful WP-CLI commands for your staging environment:"
echo "   List all users: docker exec $CONTAINER_NAME wp --path=/var/www/html --allow-root user list"
echo "   View user meta: docker exec $CONTAINER_NAME wp --path=/var/www/html --allow-root user meta list USERNAME"
echo "   Reset password: docker exec $CONTAINER_NAME wp --path=/var/www/html --allow-root user update USERNAME --user_pass=NEWPASS"
echo "   Change user role: docker exec $CONTAINER_NAME wp --path=/var/www/html --allow-root user set-role USERNAME ROLE"
