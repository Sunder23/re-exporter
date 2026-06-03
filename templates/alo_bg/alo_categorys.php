<?php

$categories = [
    [
        'id' => 'sales',
        'name' => 'Продажби (Продажи)',
        'template' => null,
        'subcategories' => [
            [
                'id' => '456',
                'name' => 'Имоти - Продажби » Апартаменти (Недвижимость - Продажа » Квартиры)',
                'template' => [
                    'path' => 'templates/alo_bg/sales/apartments.json',
                    'required_fields' => ['out_id', 'subcat_id', 'region_name', 'location_name', 'section_name', 'price', 'currency', 'type', 'area', 'construction_type', 'contacts.phones'],
                    'field_map' => [
                        'currency' => 'currency',
                        'type' => 'type_apartments_sale',
                        'construction_type' => 'construction_type_apartments',
                        'construction_stage' => 'construction_stage',
                        'floor_position' => 'floor_position',
                        'features' => 'features_apartments_sale',
                    ],
                ],
            ],
            [
                'id' => '457',
                'name' => 'Имоти - Продажби » Къщи, Вили (Недвижимость - Продажа » Дома, виллы)',
                'template' => [
                    'path' => 'templates/alo_bg/sales/houses_villas.json',
                    'required_fields' => ['out_id', 'subcat_id', 'region_name', 'location_name', 'section_name', 'price', 'currency', 'area', 'contacts.phones'],
                    'field_map' => [
                        'currency' => 'currency',
                        'house_floor' => 'house_floor',
                        'features' => 'features_houses_sale',
                    ],
                ],
            ],
            [
                'id' => '519',
                'name' => 'Имоти - Продажби » Магазини, Офиси, Кабинети, Салони (Недвижимость - Продажа » Магазины, офисы, студии, салоны красоты)',
                'template' => [
                    'path' => 'templates/alo_bg/sales/shops_offices_cabinets_salons.json',
                    'required_fields' => ['out_id', 'subcat_id', 'region_name', 'location_name', 'section_name', 'price', 'currency', 'suitable_for', 'area', 'construction_type', 'contacts.phones'],
                    'field_map' => [
                        'currency' => 'currency',
                        'suitable_for' => 'suitable_for_sale',
                        'construction_type' => 'construction_type_shops',
                        'construction_stage' => 'construction_stage',
                        'shop_floor' => 'shop_floor',
                        'building_type' => 'building_type',
                        'features' => 'features_shops_sale',
                    ],
                ],
            ],
            [
                'id' => '526',
                'name' => 'Имоти - Продажби » Парцели за застрояване, Инвестиционни проекти (Недвижимость - Продажа » Земельные участки под застройку, Инвестиционные проекты)',
                'template' => [
                    'path' => 'templates/alo_bg/sales/building-plots_investment-projects.json',
                    'required_fields' => ['out_id', 'subcat_id', 'region_name', 'location_name', 'section_name', 'price', 'currency', 'deal_type', 'area', 'regulation', 'contacts.phones'],
                    'field_map' => [
                        'currency' => 'currency',
                        'deal_type' => 'deal_type',
                        'regulation' => 'regulation',
                        'has_current' => 'has_current',
                        'has_water' => 'has_water',
                        'has_project' => 'has_project',
                        'features' => 'features_plots_sale',
                    ],
                ],
            ],
            [
                'id' => '531',
                'name' => 'Имоти - Продажби » Земеделска земя, градини, лозя, гора (Недвижимость - Продажа » Сельскохозяйственные земли, сады, виноградники, леса)',
                'template' => [
                    'path' => 'templates/alo_bg/sales/agricultural-land_gardens_vineyards_forest.json',
                    'required_fields' => ['out_id', 'subcat_id', 'region_name', 'location_name', 'section_name', 'price', 'currency', 'type', 'area', 'contacts.phones'],
                    'field_map' => [
                        'currency' => 'currency',
                        'type' => 'type_agricultural',
                        'land_category' => 'land_category',
                    ],
                ],
            ],
            [
                'id' => '523',
                'name' => 'Имоти - Продажби » Складове, промишлени и стопански имоти (Недвижимость - Продажа » Склады, промышленные и коммерческие объекты)',
                'template' => [
                    'path' => 'templates/alo_bg/sales/warehouses_industrial-and-commercial-properties.json',
                    'required_fields' => ['out_id', 'subcat_id', 'region_name', 'location_name', 'section_name', 'price', 'currency', 'type', 'area', 'contacts.phones'],
                    'field_map' => [

                        'type' => 'type_warehouses',
                        'features' => 'features_warehouses_sale',
                    ],
                ],
            ],
            [
                'id' => '525',
                'name' => 'Имоти - Продажби » Гаражи, Паркоместа (Недвижимость - Продажа » Гаражи, парковочные места)',
                'template' => [
                    'path' => 'templates/alo_bg/sales/garages_parking-spaces.json',
                    'required_fields' => ['out_id', 'subcat_id', 'region_name', 'location_name', 'section_name', 'price', 'currency', 'type', 'area', 'construction_type', 'contacts.phones'],
                    'field_map' => [
                        'currency' => 'currency',
                        'type' => 'type_garages',
                        'construction_type' => 'construction_type_garages',
                        'features' => 'features_garages_sale',
                    ],
                ],
            ],
            [
                'id' => '522',
                'name' => 'Имоти - Продажби » Хотели, Мотели (Недвижимость - Продажа » Отели, мотели)',
                'template' => [
                    'path' => 'templates/alo_bg/sales/hotels_motel.json',
                    'required_fields' => ['out_id', 'subcat_id', 'region_name', 'location_name', 'section_name', 'price', 'currency', 'area', 'construction_type', 'contacts.phones'],
                    'field_map' => [
                        'currency' => 'currency',
                        'type' => 'type_hotels_sale',
                        'hotel_category' => 'hotel_category',
                        'construction_stage' => 'construction_stage',
                        'construction_type' => 'construction_type_hotels',
                        'features' => 'features_hotels_sale',
                    ],
                ],
            ],
            [
                'id' => '521',
                'name' => 'Имоти - Продажби » Заведения (Недвижимость - Продажа » Рестораны)',
                'template' => [
                    'path' => 'templates/alo_bg/sales/restaurants.json',
                    'required_fields' => ['out_id', 'subcat_id', 'region_name', 'location_name', 'section_name', 'price', 'currency', 'area', 'construction_type', 'contacts.phones'],
                    'field_map' => [
                        'currency' => 'currency',
                        'situation' => 'situation_restaurants',
                        'construction_type' => 'construction_type_restaurants',
                        'construction_stage' => 'construction_stage',
                        'features' => 'features_restaurants_sale',
                    ],
                ],
            ],
            [
                'id' => '557',
                'name' => 'Имоти - Продажби » Заменя имоти (Недвижимость - Продажа » Заменить недвижимость)',
                'template' => [
                    'path' => 'templates/alo_bg/sales/exchange-properties.json',
                    'required_fields' => ['out_id', 'subcat_id', 'region_name', 'location_name', 'section_name', 'name', 'contacts.phones'],
                    'field_map' => [],
                ],
            ],
            [
                'id' => '556',
                'name' => 'Имоти - Продажби » Купува имоти (Недвижимость - Продажа » Покупка недвижимости)',
                'template' => [
                    'path' => 'templates/alo_bg/sales/buys-properties.json',
                    'required_fields' => ['out_id', 'subcat_id', 'region_name', 'location_name', 'price', 'currency', 'name', 'contacts.phones'],
                    'field_map' => [

                    ],
                ],
            ],
            [
                'id' => '533',
                'name' => 'Имоти - Продажби » Други имоти (Недвижимость - Продажа » Другая недвижимость)',
                'template' => [
                    'path' => 'templates/alo_bg/sales/other-properties.json',
                    'required_fields' => ['out_id', 'subcat_id', 'region_name', 'location_name', 'section_name', 'price', 'currency', 'name', 'contacts.phones'],
                    'field_map' => [

                    ],
                ],
            ],
        ],
    ],
    [
        'id' => 'rent',
        'name' => 'Наеми (Аренда)',
        'template' => null,
        'subcategories' => [
            [
                'id' => '540',
                'name' => 'Имоти - Наеми » Апартаменти под наем (Недвижимость - Аренда » Квартиры в аренду)',
                'template' => [
                    'path' => 'templates/alo_bg/rent/apartments-for-rent.json',
                    'required_fields' => ['out_id', 'subcat_id', 'region_name', 'location_name', 'section_name', 'price', 'currency', 'type', 'area', 'furniture', 'contacts.phones'],
                    'field_map' => [
                        'currency' => 'currency',
                        'type' => 'type_apartments_rent',
                        'furniture' => 'furniture_apartments',
                        'floor' => 'floor_apartments_rent',
                        'features' => 'features_apartments_rent',
                    ],
                ],
            ],
            [
                'id' => '543',
                'name' => 'Имоти - Наеми » Къщи под наем (Недвижимость - Аренда » Дома в аренду)',
                'template' => [
                    'path' => 'templates/alo_bg/rent/houses-for-rent.json',
                    'required_fields' => ['out_id', 'subcat_id', 'region_name', 'location_name', 'section_name', 'price', 'currency', 'house_floors', 'area', 'furniture', 'contacts.phones'],
                    'field_map' => [
                        'currency' => 'currency',
                        'house_floors' => 'house_floors',
                        'furniture' => 'furniture_houses',
                    ],
                ],
            ],
            [
                'id' => '542',
                'name' => 'Имоти - Наеми » Стаи под наем, Съквартиранти (Недвижимость - Аренда » Комнаты в аренду, Соседи по комнате)',
                'template' => [
                    'path' => 'templates/alo_bg/rent/rooms-for-rent_roommates.json',
                    'required_fields' => ['out_id', 'subcat_id', 'region_name', 'location_name', 'section_name', 'price', 'currency', 'type', 'has_landlords', 'contacts.phones'],
                    'field_map' => [
                        'currency' => 'currency',
                        'type' => 'type_rooms_rent',
                        'has_landlords' => 'has_landlords',
                        'prefer' => 'prefer',
                        'features' => 'features_rooms_rent',
                    ],
                ],
            ],
            [
                'id' => '546',
                'name' => 'Имоти - Наеми » Заведения под наем (Недвижимость - Аренда » Аренда ресторанов)',
                'template' => [
                    'path' => 'templates/alo_bg/rent/restaurants-for-rent.json',
                    'required_fields' => ['out_id', 'subcat_id', 'region_name', 'location_name', 'section_name', 'price', 'currency', 'type', 'area', 'has_equipment', 'contacts.phones'],
                    'field_map' => [
                        'currency' => 'currency',
                        'has_equipment' => 'has_equipment',
                        'season' => 'season',
                        'features' => 'features_restaurants_rent',
                    ],
                ],
            ],
            [
                'id' => '545',
                'name' => 'Имоти - Наеми » Магазини, Офиси, Кабинети, Салони (Недвижимость - Аренда » Магазины, офисы, студии, салоны красоты)',
                'template' => [
                    'path' => 'templates/alo_bg/rent/shops_offices_cabinets_salons.json',
                    'required_fields' => ['out_id', 'subcat_id', 'region_name', 'location_name', 'section_name', 'price', 'currency', 'suitable_for', 'area', 'contacts.phones'],
                    'field_map' => [
                        'currency' => 'currency',
                        'suitable_for' => 'suitable_for_rent',
                        'floor' => 'floor_shops_rent',
                        'situation' => 'situation_shops_rent',
                        'features' => 'features_shops_rent',
                    ],
                ],
            ],
            [
                'id' => '550',
                'name' => 'Имоти - Наеми » Гаражи, Паркинги, Паркоместа под наем (Недвижимость - Аренда » Гаражи, парковки, парковочные места в аренду)',
                'template' => [
                    'path' => 'templates/alo_bg/rent/garages_parking-lots_parking-spaces-for-rent.json',
                    'required_fields' => ['out_id', 'subcat_id', 'region_name', 'location_name', 'section_name', 'price', 'currency', 'type', 'contacts.phones'],
                    'field_map' => [
                        'type' => 'type_garages',
                        'features' => 'features_garages_rent',
                    ],
                ],
            ],
            [
                'id' => '548',
                'name' => 'Имоти - Наеми » Складове, промишлени и стопански имоти под наем (Недвижимость - Аренда » Складские, промышленные и коммерческие помещения в аренду)',
                'template' => [
                    'path' => 'templates/alo_bg/rent/warehouses_industrial-and-commercial-properties-for-rent.json',
                    'required_fields' => ['out_id', 'subcat_id', 'region_name', 'location_name', 'section_name', 'price', 'currency', 'type', 'area', 'contacts.phones'],
                    'field_map' => [

                        'type' => 'type_warehouses',
                        'features' => 'features_warehouses_rent',
                    ],
                ],
            ],
            [
                'id' => '551',
                'name' => 'Имоти - Наеми » Парцели, Терени под наем (Недвижимость - Аренда » Земельные участки, аренда земли)',
                'template' => [
                    'path' => 'templates/alo_bg/rent/plots_land-for-rent.json',
                    'required_fields' => ['out_id', 'subcat_id', 'region_name', 'location_name', 'section_name', 'price', 'currency', 'type', 'area', 'contacts.phones'],
                    'field_map' => [

                        'features' => 'features_plots_rent',
                    ],
                ],
            ],
            [
                'id' => '547',
                'name' => 'Имоти - Наеми » Хотели, Почивни станции под наем (Недвижимость - Аренда » Отели, курорты в аренду)',
                'template' => [
                    'path' => 'templates/alo_bg/rent/hotels_holiday-resorts-for-rent.json',
                    'required_fields' => ['out_id', 'subcat_id', 'region_name', 'location_name', 'section_name', 'price', 'currency', 'type', 'contacts.phones'],
                    'field_map' => [
                        'currency' => 'currency',
                        'type' => 'type_hotels_rent',
                        'category' => 'category_hotels_rent',
                        'is_working' => 'is_working',
                        'features' => 'features_hotels_rent',
                    ],
                ],
            ],
            [
                'id' => '555',
                'name' => 'Имоти - Наеми » Търси под наем (Недвижимость - Аренда » Поиск жилья в аренду)',
                'template' => [
                    'path' => 'templates/alo_bg/rent/looking-for-rent.json',
                    'required_fields' => ['out_id', 'subcat_id', 'region_name', 'location_name', 'price', 'currency', 'type', 'contacts.phones'],
                    'field_map' => [

                        'type' => 'type_looking_rent',
                    ],
                ],
            ],
        ],
    ],
];
