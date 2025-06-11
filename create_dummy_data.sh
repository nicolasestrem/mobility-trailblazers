#!/bin/bash

# Mobility Trailblazers - Dummy Data Generation
# Run these commands inside the WP-CLI container

echo "=== Creating 20 Dummy Jury Members ==="

# Jury Member 1 - Automotive Industry
docker exec mobility_wpcli wp user create jury1 dr.mueller@automotive-tech.de \
  --role=jury_member \
  --first_name="Dr. Andreas" \
  --last_name="Müller" \
  --display_name="Dr. Andreas Müller" \
  --user_pass="JuryPass2025!" \
  --meta_input='{"company":"Volkswagen AG","position":"Head of Electric Mobility","expertise":"Automotive & E-Mobility","bio":"20+ years in automotive industry, leading VW electric transformation","linkedin":"https://linkedin.com/in/andreas-mueller-vw","location":"Wolfsburg, Germany"}'

# Jury Member 2 - Public Transport
docker exec mobility_wpcli wp user create jury2 s.schneider@db-mobility.de \
  --role=jury_member \
  --first_name="Sabine" \
  --last_name="Schneider" \
  --display_name="Sabine Schneider" \
  --user_pass="JuryPass2025!" \
  --meta_input='{"company":"Deutsche Bahn AG","position":"Director Innovation & Digital","expertise":"Rail & Public Transport","bio":"Expert in digitalization of public transport systems","linkedin":"https://linkedin.com/in/sabine-schneider-db","location":"Berlin, Germany"}'

# Jury Member 3 - Aviation
docker exec mobility_wpcli wp user create jury3 m.weber@lufthansa-group.com \
  --role=jury_member \
  --first_name="Michael" \
  --last_name="Weber" \
  --display_name="Michael Weber" \
  --user_pass="JuryPass2025!" \
  --meta_input='{"company":"Lufthansa Group","position":"VP Sustainable Aviation","expertise":"Aviation & Sustainability","bio":"Leading sustainable aviation initiatives at Lufthansa","linkedin":"https://linkedin.com/in/michael-weber-lufthansa","location":"Frankfurt, Germany"}'

# Jury Member 4 - Venture Capital
docker exec mobility_wpcli wp user create jury4 j.fischer@mobility-vc.com \
  --role=jury_member \
  --first_name="Julia" \
  --last_name="Fischer" \
  --display_name="Julia Fischer" \
  --user_pass="JuryPass2025!" \
  --meta_input='{"company":"Mobility Ventures","position":"Managing Partner","expertise":"Venture Capital & Startups","bio":"Investor focused on mobility and transportation startups","linkedin":"https://linkedin.com/in/julia-fischer-vc","location":"Munich, Germany"}'

# Jury Member 5 - Government
docker exec mobility_wpcli wp user create jury5 t.bauer@bmvi.bund.de \
  --role=jury_member \
  --first_name="Thomas" \
  --last_name="Bauer" \
  --display_name="Thomas Bauer" \
  --user_pass="JuryPass2025!" \
  --meta_input='{"company":"German Federal Ministry of Transport","position":"State Secretary","expertise":"Transport Policy","bio":"Senior government official shaping national transport policy","linkedin":"https://linkedin.com/in/thomas-bauer-bmvi","location":"Berlin, Germany"}'

# Jury Member 6 - Academic
docker exec mobility_wpcli wp user create jury6 prof.lang@eth.ch \
  --role=jury_member \
  --first_name="Prof. Dr. Anna" \
  --last_name="Lang" \
  --display_name="Prof. Dr. Anna Lang" \
  --user_pass="JuryPass2025!" \
  --meta_input='{"company":"ETH Zurich","position":"Professor of Mobility Systems","expertise":"Research & Innovation","bio":"Leading researcher in smart mobility and urban transport","linkedin":"https://linkedin.com/in/anna-lang-eth","location":"Zurich, Switzerland"}'

# Jury Member 7 - Automotive Supplier
docker exec mobility_wpcli wp user create jury7 r.hoffmann@bosch.com \
  --role=jury_member \
  --first_name="Robert" \
  --last_name="Hoffmann" \
  --display_name="Robert Hoffmann" \
  --user_pass="JuryPass2025!" \
  --meta_input='{"company":"Robert Bosch GmbH","position":"Executive VP Mobility Solutions","expertise":"Automotive Technology","bio":"Expert in connected and autonomous vehicle technologies","linkedin":"https://linkedin.com/in/robert-hoffmann-bosch","location":"Stuttgart, Germany"}'

# Jury Member 8 - Logistics
docker exec mobility_wpcli wp user create jury8 c.klein@dhl.com \
  --role=jury_member \
  --first_name="Christine" \
  --last_name="Klein" \
  --display_name="Christine Klein" \
  --user_pass="JuryPass2025!" \
  --meta_input='{"company":"DHL Group","position":"Chief Innovation Officer","expertise":"Logistics & Supply Chain","bio":"Driving innovation in logistics and last-mile delivery","linkedin":"https://linkedin.com/in/christine-klein-dhl","location":"Bonn, Germany"}'

# Jury Member 9 - Energy Sector
docker exec mobility_wpcli wp user create jury9 h.richter@eon.com \
  --role=jury_member \
  --first_name="Hans" \
  --last_name="Richter" \
  --display_name="Hans Richter" \
  --user_pass="JuryPass2025!" \
  --meta_input='{"company":"E.ON SE","position":"Head of E-Mobility","expertise":"Energy & Charging Infrastructure","bio":"Leading charging infrastructure development across Europe","linkedin":"https://linkedin.com/in/hans-richter-eon","location":"Essen, Germany"}'

# Jury Member 10 - Swiss Transport
docker exec mobility_wpcli wp user create jury10 m.keller@sbb.ch \
  --role=jury_member \
  --first_name="Martin" \
  --last_name="Keller" \
  --display_name="Martin Keller" \
  --user_pass="JuryPass2025!" \
  --meta_input='{"company":"Swiss Federal Railways (SBB)","position":"Head of Digital Transformation","expertise":"Public Transport Innovation","bio":"Digitizing Swiss public transport systems","linkedin":"https://linkedin.com/in/martin-keller-sbb","location":"Bern, Switzerland"}'

# Jury Member 11 - Austrian Industry
docker exec mobility_wpcli wp user create jury11 e.wagner@magna.com \
  --role=jury_member \
  --first_name="Elisabeth" \
  --last_name="Wagner" \
  --display_name="Elisabeth Wagner" \
  --user_pass="JuryPass2025!" \
  --meta_input='{"company":"Magna International","position":"VP Future Mobility","expertise":"Automotive Engineering","bio":"Leading automotive innovation and electrification","linkedin":"https://linkedin.com/in/elisabeth-wagner-magna","location":"Graz, Austria"}'

# Jury Member 12 - Urban Planning
docker exec mobility_wpcli wp user create jury12 d.meyer@wien.gv.at \
  --role=jury_member \
  --first_name="Dr. Daniel" \
  --last_name="Meyer" \
  --display_name="Dr. Daniel Meyer" \
  --user_pass="JuryPass2025!" \
  --meta_input='{"company":"City of Vienna","position":"Director Urban Mobility","expertise":"Urban Planning & Smart Cities","bio":"Shaping Vienna smart city and mobility initiatives","linkedin":"https://linkedin.com/in/daniel-meyer-vienna","location":"Vienna, Austria"}'

# Jury Member 13 - Tech Industry
docker exec mobility_wpcli wp user create jury13 l.schulz@siemens.com \
  --role=jury_member \
  --first_name="Laura" \
  --last_name="Schulz" \
  --display_name="Laura Schulz" \
  --user_pass="JuryPass2025!" \
  --meta_input='{"company":"Siemens Mobility","position":"CEO Digital Industries","expertise":"Industrial Technology","bio":"Leading digital transformation in industrial mobility","linkedin":"https://linkedin.com/in/laura-schulz-siemens","location":"Munich, Germany"}'

# Jury Member 14 - Insurance
docker exec mobility_wpcli wp user create jury14 p.zimmermann@allianz.com \
  --role=jury_member \
  --first_name="Peter" \
  --last_name="Zimmermann" \
  --display_name="Peter Zimmermann" \
  --user_pass="JuryPass2025!" \
  --meta_input='{"company":"Allianz SE","position":"Head of Mobility Insurance","expertise":"Insurance & Risk Management","bio":"Developing insurance solutions for new mobility concepts","linkedin":"https://linkedin.com/in/peter-zimmermann-allianz","location":"Munich, Germany"}'

# Jury Member 15 - Consulting
docker exec mobility_wpcli wp user create jury15 a.wolf@mckinsey.com \
  --role=jury_member \
  --first_name="Andrea" \
  --last_name="Wolf" \
  --display_name="Andrea Wolf" \
  --user_pass="JuryPass2025!" \
  --meta_input='{"company":"McKinsey & Company","position":"Partner, Automotive Practice","expertise":"Strategy Consulting","bio":"Advising automotive companies on transformation strategies","linkedin":"https://linkedin.com/in/andrea-wolf-mckinsey","location":"Düsseldorf, Germany"}'

# Jury Member 16 - Startup Ecosystem
docker exec mobility_wpcli wp user create jury16 f.gross@rocket-internet.com \
  --role=jury_member \
  --first_name="Felix" \
  --last_name="Gross" \
  --display_name="Felix Gross" \
  --user_pass="JuryPass2025!" \
  --meta_input='{"company":"Rocket Internet","position":"Investment Director","expertise":"Startup Investment","bio":"Investing in mobility and transportation startups","linkedin":"https://linkedin.com/in/felix-gross-rocket","location":"Berlin, Germany"}'

# Jury Member 17 - Media
docker exec mobility_wpcli wp user create jury17 s.braun@handelsblatt.com \
  --role=jury_member \
  --first_name="Susanne" \
  --last_name="Braun" \
  --display_name="Susanne Braun" \
  --user_pass="JuryPass2025!" \
  --meta_input='{"company":"Handelsblatt Media Group","position":"Head of Mobility Coverage","expertise":"Business Journalism","bio":"Leading business journalist covering mobility transformation","linkedin":"https://linkedin.com/in/susanne-braun-hb","location":"Düsseldorf, Germany"}'

# Jury Member 18 - International
docker exec mobility_wpcli wp user create jury18 n.huber@bmw.com \
  --role=jury_member \
  --first_name="Nicole" \
  --last_name="Huber" \
  --display_name="Nicole Huber" \
  --user_pass="JuryPass2025!" \
  --meta_input='{"company":"BMW Group","position":"VP Global Mobility Services","expertise":"Mobility Services","bio":"Developing global mobility-as-a-service platforms","linkedin":"https://linkedin.com/in/nicole-huber-bmw","location":"Munich, Germany"}'

# Jury Member 19 - Research Institute
docker exec mobility_wpcli wp user create jury19 t.steinberg@fraunhofer.de \
  --role=jury_member \
  --first_name="Dr. Thomas" \
  --last_name="Steinberg" \
  --display_name="Dr. Thomas Steinberg" \
  --user_pass="JuryPass2025!" \
  --meta_input='{"company":"Fraunhofer Institute","position":"Director Transport Research","expertise":"Applied Research","bio":"Leading research in autonomous and connected vehicles","linkedin":"https://linkedin.com/in/thomas-steinberg-fraunhofer","location":"Stuttgart, Germany"}'

# Jury Member 20 - Digital Innovation
docker exec mobility_wpcli wp user create jury20 k.neumann@sap.com \
  --role=jury_member \
  --first_name="Katrin" \
  --last_name="Neumann" \
  --display_name="Katrin Neumann" \
  --user_pass="JuryPass2025!" \
  --meta_input='{"company":"SAP SE","position":"VP Intelligent Transportation","expertise":"Enterprise Software","bio":"Developing enterprise solutions for intelligent transportation","linkedin":"https://linkedin.com/in/katrin-neumann-sap","location":"Walldorf, Germany"}'

echo "=== Jury Members Created Successfully ==="
echo ""
echo "=== Creating 150 Dummy Candidates ==="

# ESTABLISHED COMPANIES CATEGORY (50 candidates)

# Candidate 1
docker exec mobility_wpcli wp user create candidate1 m.hartmann@mercedes.com \
  --role=candidate \
  --first_name="Dr. Marcus" \
  --last_name="Hartmann" \
  --display_name="Dr. Marcus Hartmann" \
  --user_pass="CandPass2025!" \
  --meta_input='{"company":"Mercedes-Benz Group","position":"Chief Technology Officer","category":"established","expertise":"Autonomous Driving","achievement":"Led development of Level 3 autonomous driving system","innovation_score":"","implementation_score":"","role_model_score":"","courage_score":"","bio":"Pioneering autonomous vehicle technology at Mercedes-Benz","linkedin":"https://linkedin.com/in/marcus-hartmann-mb","location":"Stuttgart, Germany"}'

# Candidate 2
docker exec mobility_wpcli wp user create candidate2 s.lehmann@audi.com \
  --role=candidate \
  --first_name="Sandra" \
  --last_name="Lehmann" \
  --display_name="Sandra Lehmann" \
  --user_pass="CandPass2025!" \
  --meta_input='{"company":"AUDI AG","position":"Head of Electric Vehicle Strategy","category":"established","expertise":"Electric Mobility","achievement":"Launched Audi e-tron family with 500km range","innovation_score":"","implementation_score":"","role_model_score":"","courage_score":"","bio":"Driving Audi electric transformation strategy","linkedin":"https://linkedin.com/in/sandra-lehmann-audi","location":"Ingolstadt, Germany"}'

# Candidate 3
docker exec mobility_wpcli wp user create candidate3 j.koch@porsche.com \
  --role=candidate \
  --first_name="Jürgen" \
  --last_name="Koch" \
  --display_name="Jürgen Koch" \
  --user_pass="CandPass2025!" \
  --meta_input='{"company":"Porsche AG","position":"Director Synthetic Fuels","category":"established","expertise":"Alternative Fuels","achievement":"Developed breakthrough synthetic fuel production process","innovation_score":"","implementation_score":"","role_model_score":"","courage_score":"","bio":"Leading sustainable fuel innovation at Porsche","linkedin":"https://linkedin.com/in/juergen-koch-porsche","location":"Stuttgart, Germany"}'

# Candidate 4
docker exec mobility_wpcli wp user create candidate4 a.peters@continental.com \
  --role=candidate \
  --first_name="Anna" \
  --last_name="Peters" \
  --display_name="Anna Peters" \
  --user_pass="CandPass2025!" \
  --meta_input='{"company":"Continental AG","position":"VP Connected Mobility","category":"established","expertise":"Vehicle Connectivity","achievement":"Created industry-leading V2X communication platform","innovation_score":"","implementation_score":"","role_model_score":"","courage_score":"","bio":"Pioneering connected vehicle technologies","linkedin":"https://linkedin.com/in/anna-peters-continental","location":"Hannover, Germany"}'

# Candidate 5
docker exec mobility_wpcli wp user create candidate5 r.scholz@zf.com \
  --role=candidate \
  --first_name="Robert" \
  --last_name="Scholz" \
  --display_name="Robert Scholz" \
  --user_pass="CandPass2025!" \
  --meta_input='{"company":"ZF Friedrichshafen","position":"Chief Innovation Officer","category":"established","expertise":"Mobility Systems","achievement":"Developed next-gen electric vehicle drivetrain","innovation_score":"","implementation_score":"","role_model_score":"","courage_score":"","bio":"Transforming automotive supplier technology","linkedin":"https://linkedin.com/in/robert-scholz-zf","location":"Friedrichshafen, Germany"}'

# Continue with more established company candidates...
# Candidate 6-15
for i in {6..15}; do
docker exec mobility_wpcli wp user create candidate$i candidate$i@company.com \
  --role=candidate \
  --first_name="Candidate" \
  --last_name="$i" \
  --display_name="Candidate $i" \
  --user_pass="CandPass2025!" \
  --meta_input='{"company":"Established Corp '$i'","position":"Innovation Lead","category":"established","expertise":"Mobility Tech","achievement":"Major innovation in mobility sector","innovation_score":"","implementation_score":"","role_model_score":"","courage_score":"","bio":"Innovation leader at established company","linkedin":"https://linkedin.com/in/candidate'$i'","location":"Germany"}'
done

# STARTUP CATEGORY (50 candidates)

# Candidate 16 - E-Scooter Startup
docker exec mobility_wpcli wp user create candidate16 l.mueller@tier.app \
  --role=candidate \
  --first_name="Lisa" \
  --last_name="Müller" \
  --display_name="Lisa Müller" \
  --user_pass="CandPass2025!" \
  --meta_input='{"company":"TIER Mobility","position":"Co-Founder & CEO","category":"startups","expertise":"Micro-Mobility","achievement":"Scaled e-scooter sharing to 200+ cities","innovation_score":"","implementation_score":"","role_model_score":"","courage_score":"","bio":"Co-founded leading European micro-mobility platform","linkedin":"https://linkedin.com/in/lisa-mueller-tier","location":"Berlin, Germany"}'

# Candidate 17 - Autonomous Delivery
docker exec mobility_wpcli wp user create candidate17 m.schmidt@starship.xyz \
  --role=candidate \
  --first_name="Max" \
  --last_name="Schmidt" \
  --display_name="Max Schmidt" \
  --user_pass="CandPass2025!" \
  --meta_input='{"company":"Starship Technologies","position":"Founder","category":"startups","expertise":"Autonomous Delivery","achievement":"Deployed 2000+ delivery robots across Europe","innovation_score":"","implementation_score":"","role_model_score":"","courage_score":"","bio":"Pioneer in autonomous last-mile delivery robots","linkedin":"https://linkedin.com/in/max-schmidt-starship","location":"Hamburg, Germany"}'

# Candidate 18 - EV Charging Network
docker exec mobility_wpcli wp user create candidate18 k.weber@fastned.nl \
  --role=candidate \
  --first_name="Kristina" \
  --last_name="Weber" \
  --display_name="Kristina Weber" \
  --user_pass="CandPass2025!" \
  --meta_input='{"company":"Fastned","position":"Country Manager Germany","category":"startups","expertise":"Charging Infrastructure","achievement":"Built 300+ fast-charging stations in DACH","innovation_score":"","implementation_score":"","role_model_score":"","courage_score":"","bio":"Expanding European fast-charging network","linkedin":"https://linkedin.com/in/kristina-weber-fastned","location":"Düsseldorf, Germany"}'

# Continue with more startup candidates...
# Candidate 19-65
for i in {19..65}; do
docker exec mobility_wpcli wp user create candidate$i candidate$i@startup.com \
  --role=candidate \
  --first_name="Startup" \
  --last_name="Founder$i" \
  --display_name="Startup Founder $i" \
  --user_pass="CandPass2025!" \
  --meta_input='{"company":"Startup '$i'","position":"Founder/CEO","category":"startups","expertise":"Mobility Innovation","achievement":"Breakthrough innovation in mobility","innovation_score":"","implementation_score":"","role_model_score":"","courage_score":"","bio":"Innovative startup founder transforming mobility","linkedin":"https://linkedin.com/in/founder'$i'","location":"DACH Region"}'
done

# POLITICS & PUBLIC COMPANIES CATEGORY (50 candidates)

# Candidate 66 - Transport Minister
docker exec mobility_wpcli wp user create candidate66 h.baerbock@bundestag.de \
  --role=candidate \
  --first_name="Dr. Helena" \
  --last_name="Baerbock" \
  --display_name="Dr. Helena Baerbock" \
  --user_pass="CandPass2025!" \
  --meta_input='{"company":"German Federal Government","position":"State Secretary for Transport","category":"politics","expertise":"Transport Policy","achievement":"Implemented €10B climate-friendly transport package","innovation_score":"","implementation_score":"","role_model_score":"","courage_score":"","bio":"Leading national transport transformation policy","linkedin":"https://linkedin.com/in/helena-baerbock","location":"Berlin, Germany"}'

# Candidate 67 - City Mayor
docker exec mobility_wpcli wp user create candidate67 t.reiter@muenchen.de \
  --role=candidate \
  --first_name="Thomas" \
  --last_name="Reiter" \
  --display_name="Thomas Reiter" \
  --user_pass="CandPass2025!" \
  --meta_input='{"company":"City of Munich","position":"Mayor","category":"politics","expertise":"Urban Mobility","achievement":"Launched largest car-free city center in Europe","innovation_score":"","implementation_score":"","role_model_score":"","courage_score":"","bio":"Transforming Munich into sustainable mobility city","linkedin":"https://linkedin.com/in/thomas-reiter-munich","location":"Munich, Germany"}'

# Candidate 68 - Public Transport Authority
docker exec mobility_wpcli wp user create candidate68 c.lindner@hvv.de \
  --role=candidate \
  --first_name="Claudia" \
  --last_name="Lindner" \
  --display_name="Claudia Lindner" \
  --user_pass="CandPass2025!" \
  --meta_input='{"company":"Hamburg Public Transport (HVV)","position":"Managing Director","category":"politics","expertise":"Public Transport","achievement":"Digitized entire Hamburg transport network","innovation_score":"","implementation_score":"","role_model_score":"","courage_score":"","bio":"Modernizing Hamburg public transport system","linkedin":"https://linkedin.com/in/claudia-lindner-hvv","location":"Hamburg, Germany"}'

# Continue with more politics/public candidates...
# Candidate 69-115
for i in {69..115}; do
docker exec mobility_wpcli wp user create candidate$i candidate$i@public.gov \
  --role=candidate \
  --first_name="Public" \
  --last_name="Official$i" \
  --display_name="Public Official $i" \
  --user_pass="CandPass2025!" \
  --meta_input='{"company":"Public Agency '$i'","position":"Director","category":"politics","expertise":"Public Policy","achievement":"Major policy innovation in mobility","innovation_score":"","implementation_score":"","role_model_score":"","courage_score":"","bio":"Public sector mobility innovation leader","linkedin":"https://linkedin.com/in/official'$i'","location":"DACH Region"}'
done

# ADDITIONAL MIXED CANDIDATES (35 candidates to reach 150 total)

# Research & Innovation candidates
for i in {116..130}; do
docker exec mobility_wpcli wp user create candidate$i candidate$i@research.edu \
  --role=candidate \
  --first_name="Research" \
  --last_name="Leader$i" \
  --display_name="Research Leader $i" \
  --user_pass="CandPass2025!" \
  --meta_input='{"company":"Research Institute '$i'","position":"Research Director","category":"established","expertise":"Mobility Research","achievement":"Breakthrough research in mobility technology","innovation_score":"","implementation_score":"","role_model_score":"","courage_score":"","bio":"Leading mobility research and development","linkedin":"https://linkedin.com/in/researcher'$i'","location":"DACH Region"}'
done

# International mobility leaders
for i in {131..145}; do
docker exec mobility_wpcli wp user create candidate$i candidate$i@global.com \
  --role=candidate \
  --first_name="Global" \
  --last_name="Leader$i" \
  --display_name="Global Leader $i" \
  --user_pass="CandPass2025!" \
  --meta_input='{"company":"Global Mobility Corp '$i'","position":"Regional Director DACH","category":"established","expertise":"International Mobility","achievement":"Expanding innovative mobility solutions in DACH","innovation_score":"","implementation_score":"","role_model_score":"","courage_score":"","bio":"International mobility innovation expert","linkedin":"https://linkedin.com/in/global'$i'","location":"DACH Region"}'
done

# Sustainability & Future Mobility experts
for i in {146..150}; do
docker exec mobility_wpcli wp user create candidate$i candidate$i@future.com \
  --role=candidate \
  --first_name="Future" \
  --last_name="Mobility$i" \
  --display_name="Future Mobility $i" \
  --user_pass="CandPass2025!" \
  --meta_input='{"company":"Future Mobility '$i'","position":"Sustainability Director","category":"startups","expertise":"Sustainable Mobility","achievement":"Revolutionary sustainable mobility solution","innovation_score":"","implementation_score":"","role_model_score":"","courage_score":"","bio":"Pioneering sustainable mobility solutions","linkedin":"https://linkedin.com/in/future'$i'","location":"DACH Region"}'
done

echo "=== All 150 Candidates Created Successfully ==="
echo ""
echo "=== Summary ==="
echo "✅ 20 Jury Members created"
echo "✅ 150 Candidates created"
echo "   - 50 Established Companies"
echo "   - 50 Startups"
echo "   - 50 Politics & Public"
echo ""
echo "Default passwords:"
echo "Jury: JuryPass2025!"
echo "Candidates: CandPass2025!"
echo ""
echo "Next steps:"
echo "1. Create custom fields for evaluation scores"
echo "2. Set up voting interface for jury members"
echo "3. Configure candidate profiles display"
echo "4. Test the evaluation workflow"