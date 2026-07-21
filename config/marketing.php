<?php

/*
|--------------------------------------------------------------------------
| Contenu du site vitrine (marketing)
|--------------------------------------------------------------------------
| Porté depuis le design system Noctis (ui_kits/marketing-site). Contenu
| volontairement en anglais : le site vitrine reprend la copie de
| testevtc.online, distincte du tunnel de réservation (français). Ne pas
| traduire l'un dans l'autre — cf. README du design system.
|
| Le contact (adresse / téléphone / email) reste à renseigner : sur le site
| source ce bloc était du contenu de démo WordPress non édité, pas les vraies
| coordonnées. À remplacer par les infos réelles du client.
*/

return [

    'contact' => [
        'address' => '[Adresse à renseigner]',
        'city' => 'Paris',
        'phone' => '[Téléphone à renseigner]',
        'email' => '[Email à renseigner]',
        'availability' => 'Available 24/7',
    ],

    'competences' => [
        ['PROFESSIONALISM', 'Our carefully selected chauffeurs are trained to deliver structured and discreet service at every stage of your journey. We operate modern, well-maintained vehicles to ensure safety, comfort and consistent reliability across all transfers.'],
        ['COMMITMENT', 'With 24/7 availability and simplified booking management, we streamline every stage of your transportation planning. Transparency, responsiveness and operational efficiency remain central to our service standards.'],
    ],

    'why_choose' => [
        'Premium Airport Transfers in Paris',
        'Structured European Long-Distance Travel',
        '24/7 Private Chauffeur Availability',
        'Fixed & Transparent Pricing',
        'Modern High-End Fleet',
    ],

    'fleet' => [
        ['name' => 'Sedan', 'sub' => 'C-HR or similar', 'pax' => 4, 'bags' => 3],
        ['name' => 'Berline', 'sub' => 'S / E-Class or similar (Mercedes-Benz)', 'pax' => 4, 'bags' => 4],
        ['name' => 'Van', 'sub' => 'V-Class or similar', 'pax' => 6, 'bags' => 6],
    ],

    'services' => [
        'airport-transfer' => [
            'nav' => 'Airport Transfer',
            'title' => 'Airport Transfer',
            'hero_title' => 'Premium Paris Airport Transfer Service — Available 24/7',
            'intro' => 'Arrive and depart in absolute comfort. We provide refined, door-to-door airport transfers in Paris, including Charles de Gaulle (CDG), Orly (ORY) and Beauvais (BVA), as well as long-distance private chauffeur services across France and Europe. Every journey is managed with discretion, punctuality and seamless coordination from arrival to final destination.',
            'sub' => 'Executive Airport Transfers Across Paris and Major European Cities',
            'sub_text' => 'Our executive airport transfer service connects Paris airports to city hotels, business districts and private residences, offering luxury vehicles, experienced chauffeurs and transparent pricing. Whether traveling for corporate meetings or leisure stays, we ensure a smooth and efficient transfer tailored to your schedule.',
            'bullets' => ['Professional Airport Chauffeurs', '24/7 Paris Airport Transfer Availability', 'Real-Time Flight Monitoring', 'Fixed Pricing for All Airport Transfers', 'Meet & Greet Airport Transfer Service', 'Executive Vehicles for Premium Airport Transfers'],
            'why' => [
                ['On-Time Airport Pickups', 'We monitor flight schedules in real time to adjust every arrival and departure transfer.'],
                ['Smooth Terminal Coordination', 'Meet & greet assistance ensures a seamless transition from airport to destination.'],
                ['Direct City & European Connections', 'Comfortable transfers from Paris airports to hotels, business districts and European cities.'],
                ['Discreet Executive Service', 'Professional chauffeurs trained for business and international travelers requiring privacy and reliability.'],
                ['Fixed Pricing Before Departure', 'Transparent airport transfer rates confirmed in advance, with no hidden charges.'],
                ['Simple & Fast Reservation', 'Book your airport transfer in just a few steps with immediate confirmation and clear travel details.'],
            ],
        ],
        'worldwide-transportation' => [
            'nav' => 'Europe-Wide Transfer',
            'title' => 'Europe-Wide Transfer',
            'hero_title' => 'Executive Europe-Wide Transfers from Paris & Private Long-Distance Services',
            'intro' => 'Travel across Europe with complete confidence. From Paris airports to major European cities, every long-distance journey is managed with discretion, precision and a consistently refined standard of service. Each transfer is carefully coordinated to ensure punctual departures, efficient routing and extended travel comfort throughout your journey.',
            'sub' => 'Dedicated Chauffeur Travel Across Major European Cities',
            'sub_text' => 'Our service is designed for uninterrupted travel between Paris and key European business and cultural destinations. With experienced drivers familiar with international routes and border logistics, we provide a smooth and flexible alternative to traditional travel options.',
            'bullets' => ['Private Chauffeur for European Travel', 'Direct Departures from Paris', 'Carefully Planned Long-Distance Transfers', 'Pre-Agreed Fixed Fare', 'Discreet & Professional Service', 'Executive Vehicles for Extended Comfort'],
            'why' => [
                ['Direct European Transfers', 'Seamless long-distance connections from Paris airports to major European cities.'],
                ['Cross-Border Expertise', 'Professional coordination of European routes and border procedures to ensure smooth travel.'],
                ['Premium Long-Distance Fleet', 'Modern executive vehicles tailored for efficient and comfortable European transfers.'],
                ['Transparent Long-Distance Rates', 'Clear, fixed pricing structured for European routes with no hidden costs or unexpected charges.'],
                ['Dedicated Private Chauffeur', 'Experienced chauffeurs providing discreet, attentive and highly professional service throughout your journey.'],
                ['24/7 Availability', 'Round-the-clock coordination to accommodate early departures, late arrivals and schedule adjustments.'],
            ],
        ],
        'corporate-travel' => [
            'nav' => 'Corporate Travel',
            'title' => 'Corporate Travel',
            'hero_title' => 'Corporate Chauffeur Services for International Business Travel',
            'intro' => "Business travel demands precision, discretion and absolute reliability. Our corporate transfer service connects Paris airports to Europe's key financial and business centers, ensuring seamless ground transportation for business and corporate teams. Every journey is meticulously coordinated to match your schedule, delivering efficiency and uncompromising professional standards.",
            'sub' => 'Reliable Ground Transportation for Corporate Mobility',
            'sub_text' => 'Designed for senior executives and international professionals, our service prioritizes punctuality, privacy and structured travel management. From airport transfers to cross-border business trips, we provide refined transportation solutions aligned with corporate expectations.',
            'bullets' => ['Professional Airport Transfers', 'Cross-Border European Travel', 'Priority Business Scheduling', 'Fixed & Transparent Pricing', 'Discreet and Confidential Service', 'Dedicated Corporate Chauffeurs 24/7'],
            'why' => [
                ['Time-Critical Coordination', 'Precise scheduling to support meetings, conferences and travel.'],
                ['Discreet & Confidential', 'Strict professional discretion for sensitive corporate movements.'],
                ['Business-Class Fleet', 'Refined vehicles selected for comfort, privacy and productivity.'],
                ['European Business Connectivity', 'Direct transfers between Paris airports and key European business hubs.'],
                ['Executive-Level Service', 'Professional service standards aligned with international business expectations.'],
                ['Priority Reservation', 'Structured booking management tailored for recurring executive travel needs.'],
            ],
        ],
        'charter-service' => [
            'nav' => 'Charter Service',
            'title' => 'Charter Service',
            'hero_title' => 'Private Chauffeur Charter Service in Paris & Europe',
            'intro' => 'Our private charter service provides complete vehicle and chauffeur availability structured around your schedule and travel objectives. Whether for corporate engagements, private events, VIP guests or extended European journeys, we deliver fully dedicated transportation designed for comfort, discretion and refined execution.',
            'sub' => 'Tailored Private Mobility for Structured Travel',
            'sub_text' => 'Private vehicle availability structured around your daily agenda ensures seamless mobility from start to finish. Ideal for business meetings, private functions or high-profile engagements, this charter service delivers flexibility, controlled scheduling and refined travel management throughout the day.',
            'bullets' => ['Private Professional Service', 'Dedicated Vehicle Access', 'Flexible Daily Booking', 'Professional Charter Chauffeurs', 'Extended Daily Availability', 'European Travel Coverage'],
            'why' => [
                ['Full-Day Flexibility', 'Enjoy complete control of your schedule with vehicle availability tailored to your daily itinerary and time requirements.'],
                ['Private & Exclusive Use', 'Your vehicle is fully dedicated to you, ensuring privacy, comfort and uninterrupted travel throughout the day.'],
                ['Business & Event Ready', 'Ideal for corporate engagements, private events and high-profile occasions requiring structured and reliable mobility.'],
                ['Refined Comfort', 'Premium vehicles selected to provide a smooth and comfortable experience for extended daily travel.'],
                ['Transparent Charter Rates', 'Clear and structured pricing adapted to hourly or full-day bookings, with no hidden costs.'],
                ['Professional Coordination', 'Carefully organized service management to ensure seamless logistics and punctual execution.'],
            ],
        ],
        'medical-transport' => [
            'nav' => 'Medical Transport',
            'title' => 'Medical Transport',
            'hero_title' => 'Private Medical Travel Transfers in Paris',
            'intro' => 'Discreet premium airport transfers arranged for international patients traveling to France for specialized medical care. From Paris airports to leading private clinics, hospitals and specialized medical centers, each journey is handled with complete confidentiality, refined comfort and precise coordination.',
            'sub' => 'A Reassuring & Comfortable Arrival',
            'sub_text' => 'Our personalized assistance ensures a reassuring arrival for both patients and their families. Spacious, well-appointed vehicles designed for tranquility create a calm environment to recover from travel. Beyond the drive itself, chauffeurs offer a warm welcome and attentive support adapted to the unique needs of each guest.',
            'bullets' => ['Dedicated Transfers to Leading Medical Institutions', 'Flight Coordination & Structured Scheduling', 'Personalized Door-to-Door Assistance', 'Spacious & Comfort-Focused Vehicles', 'Professional Chauffeurs for International Guests', 'Absolute Privacy & Discretion'],
            'why' => [
                ['Direct Transfers to Medical Facilities', 'Reliable connections between Paris airports, private clinics and specialized healthcare institutions.'],
                ['Flight & Arrival Coordination', 'Coordinated pickup planning to reduce waiting time and simplify airport procedures.'],
                ['Dedicated Professional Chauffeurs', 'Experienced drivers trained to assist international guests with courtesy, patience and attentive care.'],
                ['24/7 Availability & Premium Support', 'Round-the-clock service ensuring flexible transfers aligned with flight arrivals and medical appointments.'],
                ['Thoughtfully Equipped Vehicles', 'Spacious interiors designed to provide physical ease and a smooth ride following long-distance travel.'],
                ['Respectful & Attentive Service', 'A considerate approach focused on comfort, reassurance and individual needs.'],
            ],
        ],
        'special-event-limousine' => [
            'nav' => 'Special Event Limousine',
            'title' => 'Special Event Limousine',
            'hero_title' => 'Chauffeured Limousine Service for Weddings, Galas & Private Events in Paris',
            'intro' => 'For weddings, galas, premieres and private celebrations, a chauffeured arrival sets the tone for the evening. Our special event service pairs an immaculately presented vehicle with a discreet, punctual chauffeur, so every guest arrives and departs exactly on schedule.',
            'sub' => 'An Elevated Arrival for Every Occasion',
            'sub_text' => 'From a single evening booking to full coordination across a wedding party or corporate gala, we structure vehicle availability and routing around your event timeline — including waiting time between the ceremony, photos and reception.',
            'bullets' => ['Weddings, Galas & Private Celebrations', 'Immaculately Presented Vehicles', 'Flexible Waiting & Multi-Stop Routing', 'Coordinated Multi-Vehicle Bookings', 'Formal, Discreet Chauffeur Service', 'Fixed Pricing Agreed in Advance'],
            'why' => [
                ['Occasion-Ready Presentation', 'Vehicles prepared to an impeccable standard for photographs and formal arrivals.'],
                ['Flexible Timing', 'Waiting time built into the booking for ceremonies, photo sessions and receptions.'],
                ['Multi-Vehicle Coordination', 'Structured scheduling for wedding parties, galas and group celebrations.'],
                ['Formal Chauffeur Etiquette', 'Chauffeurs trained for the discretion and formality that special occasions require.'],
                ['Fixed Event Pricing', 'Rates agreed before the day — no surprises once the celebration begins.'],
                ['Dedicated Event Support', 'A single point of contact to coordinate timing changes on the day itself.'],
            ],
        ],
    ],
];
