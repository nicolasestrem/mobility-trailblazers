import csv
import random

# Define the original 3 candidates
original_candidates = [
    {
        "ID": 1,
        "Name": "Judith Häberli",
        "Organisation": "Urban Connect",
        "Position": "CEO & Co-Founder",
        "LinkedIn-Link": "https://linkedin.com/in/judith-haeberli",
        "Webseite": "https://urbanconnect.ch",
        "Article about coming of age": "https://example.com/article-judith",
        "Description": "Mut & Pioniergeist: Judith zeigt außergewöhnlichen Mut bei der Entwicklung innovativer urbaner Mobilitätslösungen und geht als Pionierin neue Wege in der Stadtentwicklung. Innovationsgrad: Ihre technologischen Ansätze zur Vernetzung städtischer Mobilität setzen neue Maßstäbe und bieten disruptive Lösungen für urbane Herausforderungen. Umsetzungskraft & Wirkung: Durch konkrete Projekte in mehreren Städten hat sie bereits messbare Verbesserungen der Mobilitätsinfrastruktur erreicht. Relevanz für die Mobilitätswende: Ihre Arbeit trägt direkt zur nachhaltigen Transformation des städtischen Verkehrs bei und fördert umweltfreundliche Alternativen. Vorbildfunktion & Sichtbarkeit: Als weibliche Führungskraft im Tech-Bereich inspiriert sie andere und ist regelmäßig auf Konferenzen als Speakerin aktiv. Persönlichkeit & Motivation: Ihre Leidenschaft für nachhaltige Stadtentwicklung und ihr Engagement für die Gemeinschaft prägen ihre visionäre Herangehensweise.",
        "Category": "Startup",
        "Status": "Top 50: Yes"
    },
    {
        "ID": 2,
        "Name": "Prof. Dr. Uwe Schneidewind",
        "Organisation": "Stadt Wuppertal",
        "Position": "Oberbürgermeister",
        "LinkedIn-Link": "https://linkedin.com/in/uwe-schneidewind",
        "Webseite": "https://wuppertal.de",
        "Article about coming of age": "https://example.com/article-schneidewind",
        "Description": "Mut & Pioniergeist: Als Oberbürgermeister wagt er mutige Schritte zur Transformation der städtischen Mobilität und setzt innovative Konzepte gegen traditionelle Widerstände durch. Innovationsgrad: Seine Ansätze zur Bürgerbeteiligung und partizipativen Stadtplanung revolutionieren die Art, wie Mobilitätsprojekte entwickelt und umgesetzt werden. Umsetzungskraft & Wirkung: Unter seiner Führung wurden bereits mehrere wegweisende Mobilitätsprojekte realisiert, die Wuppertal zu einem Vorbild für andere Städte machen. Relevanz für die Mobilitätswende: Seine Politik fokussiert konsequent auf nachhaltige Verkehrslösungen und die Reduzierung des motorisierten Individualverkehrs. Vorbildfunktion & Sichtbarkeit: Als prominenter Verfechter der Verkehrswende ist er national und international als Experte anerkannt und inspiriert andere Städte. Persönlichkeit & Motivation: Seine wissenschaftliche Expertise kombiniert mit politischem Gestaltungswillen macht ihn zu einem authentischen Akteur der Transformation.",
        "Category": "Gov",
        "Status": "Top 50: Yes"
    },
    {
        "ID": 3,
        "Name": "Anna-Theresa Korbutt",
        "Organisation": "HVV",
        "Position": "Leiterin Digitale Services",
        "LinkedIn-Link": "https://linkedin.com/in/anna-theresa-korbutt",
        "Webseite": "https://hvv.de",
        "Article about coming of age": "https://example.com/article-korbutt",
        "Description": "Mut & Pioniergeist: Sie treibt die Digitalisierung des öffentlichen Nahverkehrs voran und entwickelt mutig neue Serviceangebote, die den ÖPNV für alle Nutzergruppen attraktiver machen. Innovationsgrad: Ihre digitalen Lösungen für nahtlose Mobilitätserfahrungen setzen neue Standards in der Branche und verbessern die Nutzererfahrung erheblich. Umsetzungskraft & Wirkung: Durch ihre Projekte konnte die Nutzerzufriedenheit des HVV signifikant gesteigert und neue Zielgruppen für den öffentlichen Verkehr gewonnen werden. Relevanz für die Mobilitätswende: Ihre Arbeit macht den ÖPNV als Alternative zum Individualverkehr attraktiver und trägt zur Verkehrswende bei. Vorbildfunktion & Sichtbarkeit: Als Expertin für digitale Mobilität ist sie gefragte Speakerin und teilt ihr Wissen aktiv mit der Fachcommunity. Persönlichkeit & Motivation: Ihre nutzerorientierte Denkweise und ihr Verständnis für technologische Möglichkeiten prägen ihre erfolgreiche Arbeit im Mobilitätssektor.",
        "Category": "Tech",
        "Status": "Top 50: No"
    }
]

# Define templates for generating new candidates
startup_names = [
    "Dr. Michael Weber", "Elena Rodriguez", "Tobias Wagner", "Nina Richter", "Florian Becker", 
    "Jennifer Walsh", "Sophie Laurent", "Benjamin Lee", "Priya Sharma", "Ahmed Al-Mahmoud",
    "Oliver Schmidt", "Isabella Rossi", "Marco Rossi", "Lucas Andersson", "Thomas Andersen",
    "Melanie Chen", "Roberto Silva", "Amelia Thompson", "Nina Petersen", "Daniel Rodriguez",
    "Luca Ferrari", "Sara Johansson", "James Mitchell", "Antoine Dubois", "Erik Larsson"
]

gov_names = [
    "Marcus Hoffmann", "Alexandra Schneider", "Dr. Stefan Richter", "Sabine Fischer", 
    "Anna Hoffmann", "Michael Braun", "Carmen Rodriguez", "Stefan Huber", "Dr. Anna Koller",
    "Peter Gruber", "Alexander Novak", "Yasmin Hassan", "Julia Bauer", "Thomas Keller",
    "Stefan Weber", "Dr. Sylvia Kaufmann", "Martin Weber", "Marion Hoffmann", "Sarah Bergmann",
    "Viktor Petrov", "Katja Schneider", "Martin Koller", "Monika Steiner", "Dr. Sabine Kraft"
]

tech_names = [
    "Sarah Zimmermann", "Dr. Thomas Müller", "Dr. Petra Waldmann", "Dr. Andreas Müller",
    "Claudia Weber", "Jan Kowalski", "Maria Schneider", "Dr. Martin Keller", "Dr. Christina Müller",
    "Dr. Frank Weber", "Dr. Elisabeth Wagner", "Dr. Sophia Keller", "Dr. Michael Braun",
    "Dr. Andreas Herrmann", "Maria Santos", "Dr. Georg Huber", "Rahul Patel", "Dr. Ingrid Schuster",
    "Dr. Alexander Richter", "Dr. Petra Wolff", "Dr. Matthias Huber", "Kevin O'Sullivan", 
    "Dr. Paul Richter", "Dr. Rebecca Zimmermann", "Dr. Claudia Reinhardt"
]

startup_orgs = [
    "FlexMobility", "CargoRider", "GreenMobility", "ShareNow", "MicroMobility", 
    "Mobility Ventures", "CleverShuttle", "UrbanFlow", "LogiGreen", "CargoFlow",
    "Ridepooling Solutions", "E-Cargo Solutions", "ViaVan", "Voi Technology", "Swapfiets",
    "Bolt", "MicroTransit", "Shared Mobility Solutions", "FleetOptimizer", "eBike Innovation",
    "Green Delivery", "Nordic E-Solutions", "Autonomous Solutions", "EuroMobility", "Smart Traffic Systems"
]

gov_orgs = [
    "Stadt München", "Stadt Wien", "Stadt Hamburg", "Stadt Dresden", "Stadt Graz",
    "Stadt Linz", "Stadt Basel", "Stadt Freiburg", "Stadt Heidelberg", "Stadt Klagenfurt",
    "Stadt St. Pölten", "Stadt Mannheim", "Stadt Innsbruck", "Stadt Salzburg", "Stadt Villach",
    "Stadt Dornbirn", "Stadt Konstanz", "Stadt Passau", "Stadt Lübeck", "Stadt Eisenstadt",
    "Stadt Rostock", "Stadt Bregenz", "Stadt Kufstein", "Stadt Ulm", "ETH Zürich"
]

tech_orgs = [
    "BVG", "DB Regio", "Siemens Mobility", "ÖBB", "Deutsche Bahn", "NextGen Mobility",
    "Volkswagen Group", "Bosch Mobility Solutions", "SBB", "Continental", "Kapsch TrafficCom",
    "Mercedes-Benz", "Porsche Digital", "Audi", "Last Mile Solutions", "Knorr-Bremse",
    "NeoMobility", "ZKW Group", "Fraunhofer IAO", "AVL List", "Infineon", "Dublin Bus Connect",
    "MAN Truck & Bus", "ADAC", "TUM"
]

positions_startup = [
    "CTO", "Founder & CEO", "Co-Founder & CTO", "Country Manager Deutschland", "Co-Founder",
    "Managing Partner", "Country Manager DACH", "CEO & Co-Founder", "Founder", "Founder",
    "CEO", "Co-Founder", "Country Manager DACH", "Head of DACH Operations", "Country Manager Deutschland",
    "Regional Director DACH", "Regional Director Europe", "Country Manager DACH", "Co-Founder & CEO", "Founder & CTO",
    "Co-Founder", "CEO", "Head of Europe", "Strategy Director", "Founder"
]

positions_gov = [
    "Referent für Stadtplanung", "Stadträtin für Verkehr", "Koordinator Verkehrswende", "Beigeordnete für Verkehr",
    "Mobilitätsstadträtin", "Verkehrsreferent", "Vorsteherin Mobilitätsdepartement", "Baubürgermeister", "Umwelt- und Verkehrsdezernentin",
    "Verkehrsreferent", "Mobilitätsstadtrat", "Erste Bürgermeisterin", "Leiterin Mobilitätsplanung", "Verkehrsstadtrat",
    "Mobilitätsstadtrat", "Stadtplanungsdirektorin", "Erster Bürgermeister", "Bürgermeisterin", "Senatorin für Bau und Verkehr",
    "Bürgermeister", "Senatorin für Infrastruktur", "Stadtrat für Mobilität", "Bürgermeisterin", "Digitalisierungsbürgermeisterin"
]

positions_tech = [
    "Abteilungsleiterin Innovation", "Leiter Digitalisierung", "Head of Innovation", "Leiter Digitale Transformation",
    "Senatorin für Verkehr", "Founder", "Head of Digital Mobility", "Director Innovation", "Leiterin Innovation & Digitalisierung",
    "Head of Autonomous Driving", "Director Innovation", "Director Digital Services", "VP Mobility Services", "Head of Autonomous Driving",
    "CTO", "Director Digital Solutions", "Head of AI", "Head of Intelligent Lighting", "Abteilungsleiter Mobilität",
    "Director E-Mobility", "Director Automotive Solutions", "Project Director", "Director Digital Solutions", "Leiterin Innovation & Mobilität", "Leiterin Forschung & Entwicklung"
]

def generate_description_template():
    """Generate a template description with the 6 criteria in German"""
    templates = [
        "Mut & Pioniergeist: {name} entwickelt mutig innovative Lösungen und revolutioniert {field}. Innovationsgrad: {innovation}. Umsetzungskraft & Wirkung: {implementation}. Relevanz für die Mobilitätswende: {relevance}. Vorbildfunktion & Sichtbarkeit: {visibility}. Persönlichkeit & Motivation: {personality}.",
        "Mut & Pioniergeist: {name} treibt mutig die Transformation von {field} voran und setzt neue Standards. Innovationsgrad: {innovation}. Umsetzungskraft & Wirkung: {implementation}. Relevanz für die Mobilitätswende: {relevance}. Vorbildfunktion & Sichtbarkeit: {visibility}. Persönlichkeit & Motivation: {personality}.",
        "Mut & Pioniergeist: {name} revolutioniert mutig {field} und entwickelt bahnbrechende Konzepte. Innovationsgrad: {innovation}. Umsetzungskraft & Wirkung: {implementation}. Relevanz für die Mobilitätswende: {relevance}. Vorbildfunktion & Sichtbarkeit: {visibility}. Persönlichkeit & Motivation: {personality}."
    ]
    return random.choice(templates)

def generate_25_candidates():
    """Generate 25 additional candidates"""
    candidates = []
    
    # Split evenly across categories (8 Startup, 8 Gov, 9 Tech)
    categories = ['Startup'] * 8 + ['Gov'] * 8 + ['Tech'] * 9
    random.shuffle(categories)
    
    for i, category in enumerate(categories, start=4):  # Start from ID 4
        if category == 'Startup':
            name = random.choice(startup_names)
            org = random.choice(startup_orgs)
            position = random.choice(positions_startup)
        elif category == 'Gov':
            name = random.choice(gov_names)
            org = random.choice(gov_orgs)
            position = random.choice(positions_gov)
        else:  # Tech
            name = random.choice(tech_names)
            org = random.choice(tech_orgs)
            position = random.choice(positions_tech)
        
        # Generate Top 50 status (roughly 60% Yes, 40% No)
        top_50_status = "Top 50: Yes" if random.random() < 0.6 else "Top 50: No"
        
        # Create description with German criteria
        desc_template = generate_description_template()
        description = desc_template.format(
            name=name.split()[-1],  # Use last name
            field="den Mobilitätsbereich",
            innovation="Innovative Technologien setzen neue Standards für nachhaltige Mobilität",
            implementation="Erfolgreiche Projekte wurden bereits in mehreren Städten umgesetzt",
            relevance="Die Arbeit trägt direkt zur Mobilitätswende bei",
            visibility="Als Experte/in inspiriert er/sie andere in der Branche",
            personality="Engagement für Nachhaltigkeit und technische Innovation prägen die Arbeit"
        )
        
        candidate = {
            "ID": i,
            "Name": name,
            "Organisation": org,
            "Position": position,
            "LinkedIn-Link": f"https://linkedin.com/in/{name.lower().replace(' ', '-').replace('.', '')}",
            "Webseite": f"https://{org.lower().replace(' ', '')}.{'de' if category == 'Startup' else 'com' if category == 'Tech' else 'de'}",
            "Article about coming of age": f"https://example.com/article-{name.split()[-1].lower()}",
            "Description": description,
            "Category": category,
            "Status": top_50_status
        }
        candidates.append(candidate)
        
        # Remove used names to avoid duplicates
        if category == 'Startup':
            startup_names.remove(name)
        elif category == 'Gov':
            gov_names.remove(name)
        else:
            tech_names.remove(name)
    
    return candidates

def create_csv_file(filename="test-candidates.csv"):
    """Create the CSV file with original + 25 new candidates"""
    all_candidates = original_candidates + generate_25_candidates()
    
    fieldnames = ["ID", "Name", "Organisation", "Position", "LinkedIn-Link", "Webseite", 
                  "Article about coming of age", "Description", "Category", "Status"]
    
    # Write with UTF-8 BOM encoding
    with open(filename, 'w', newline='', encoding='utf-8-sig') as csvfile:
        writer = csv.DictWriter(csvfile, fieldnames=fieldnames)
        writer.writeheader()
        for candidate in all_candidates:
            writer.writerow(candidate)
    
    print(f"CSV file '{filename}' created successfully with {len(all_candidates)} candidates!")
    print(f"Categories distribution:")
    startup_count = len([c for c in all_candidates if c['Category'] == 'Startup'])
    gov_count = len([c for c in all_candidates if c['Category'] == 'Gov'])
    tech_count = len([c for c in all_candidates if c['Category'] == 'Tech'])
    print(f"- Startup: {startup_count}")
    print(f"- Gov: {gov_count}")
    print(f"- Tech: {tech_count}")
    
    top_50_count = len([c for c in all_candidates if 'Yes' in c['Status']])
    print(f"- Top 50: Yes: {top_50_count}")
    print(f"- Top 50: No: {len(all_candidates) - top_50_count}")

if __name__ == "__main__":
    create_csv_file()