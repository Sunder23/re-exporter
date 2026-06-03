<?php

$categories = [
    [
        'id' => 'sales',
        'name' => 'Продажби (Продажи)',
        'template' => null,
        'subcategories' => [
            [
                'id' => 'apartments_sale',
                'name' => 'Апартаменти (Квартиры)',
                'template' => [
                    'path' => 'templates/olx/sales/apartments_sale.csv',
                    'required_fields' => ['title', 'description', 'location_city', 'price_value', 'price_currency', 'heat', 'furn', 'cstate', 'pass', 'floors', 'floor', 'atype', 'space', 'cyear', 'ctype'],
                ],
            ],
            [
                'id' => 'offices_sale',
                'name' => 'Офиси (Офисы)',
                'template' => [
                    'path' => 'templates/olx/sales/offices_sale.csv',
                    'required_fields' => ['title', 'description', 'location_city', 'price_value', 'price_currency', 'heat', 'floors', 'floor', 'space', 'cyear', 'ctype'],
                ],
            ],
            [
                'id' => 'shops_sale',
                'name' => 'Магазини (Магазины)',
                'template' => [
                    'path' => 'templates/olx/sales/shops_sale.csv',
                    'required_fields' => ['title', 'description', 'location_city', 'price_value', 'price_currency', 'heat', 'floors', 'space', 'cyear', 'ctype', 'mestopolozhenie'],
                ],
            ],
            [
                'id' => 'garages_sale',
                'name' => 'Гаражи, паркоместа (Гаражи, парковочные места)',
                'template' => [
                    'path' => 'templates/olx/sales/garages_sale.csv',
                    'required_fields' => ['title', 'description', 'location_city', 'price_value', 'price_currency', 'space', 'power', 'water'],
                ],
            ],
            [
                'id' => 'plots_sale',
                'name' => 'Парцели (Участки)',
                'template' => [
                    'path' => 'templates/olx/sales/plots_sale.csv',
                    'required_fields' => ['title', 'description', 'location_city', 'price_value', 'price_currency', 'space', 'power', 'water', 'reg'],
                ],
            ],
            [
                'id' => 'houses_sale',
                'name' => 'Къщи, вили (Дома, виллы)',
                'template' => [
                    'path' => 'templates/olx/sales/houses_sale.csv',
                    'required_fields' => ['title', 'description', 'location_city', 'price_value', 'price_currency', 'heat', 'cstate', 'space', 'cyear', 'ctype', 'yspace', 'floors'],
                    'field_map' => [
                        'floors' => 'house_floors',
                    ],
                ],
            ],
            [
                'id' => 'warehouses_sale',
                'name' => 'Складове (Склады)',
                'template' => [
                    'path' => 'templates/olx/sales/warehouses_sale.csv',
                    'required_fields' => ['title', 'description', 'location_city', 'price_value', 'price_currency', 'heat', 'space', 'cyear', 'ctype'],
                ],
            ],
            [
                'id' => 'restaurants_sale',
                'name' => 'Заведения (Заведения)',
                'template' => [
                    'path' => 'templates/olx/sales/restaurants_sale.csv',
                    'required_fields' => ['title', 'description', 'location_city', 'price_value', 'price_currency', 'heat', 'space', 'cyear', 'ctype'],
                ],
            ],
            [
                'id' => 'hotels_sale',
                'name' => 'Хотели (Гостиницы)',
                'template' => [
                    'path' => 'templates/olx/sales/hotels_sale.csv',
                    'required_fields' => ['title', 'description', 'location_city', 'price_value', 'price_currency', 'heat', 'space', 'cyear', 'ctype'],
                ],
            ],
            [
                'id' => 'agricultural_sale',
                'name' => 'Земеделски имоти (Сельскохозяйственные объекты)',
                'template' => [
                    'path' => 'templates/olx/sales/agricultural_sale.csv',
                    'required_fields' => ['title', 'description', 'location_city', 'price_value', 'price_currency', 'space', 'pact', 'cat', 'type'],
                ],
            ],
            [
                'id' => 'floor_sale',
                'name' => 'Етаж от къща (Этаж дома)',
                'template' => [
                    'path' => 'templates/olx/sales/floor_sale.csv',
                    'required_fields' => ['title', 'description', 'location_city', 'price_value', 'price_currency', 'heat', 'space', 'cyear', 'ctype', 'floors'],
                    'field_map' => [
                        'floors' => 'floor_sale_floors',
                    ],
                ],
            ],
            [
                'id' => 'industrial_sale',
                'name' => 'Промишлени сгради (Промышленные здания)',
                'template' => [
                    'path' => 'templates/olx/sales/industrial_sale.csv',
                    'required_fields' => ['title', 'description', 'location_city', 'price_value', 'price_currency', 'heat', 'floors', 'space', 'cyear', 'ctype'],
                ],
            ],
            [
                'id' => 'new_construction',
                'name' => 'Ново строителство (Новостройки)',
                'template' => [
                    'path' => 'templates/olx/sales/new_construction.csv',
                    'required_fields' => ['title', 'description', 'location_city', 'price_value', 'price_currency'],
                ],
            ],
            [
                'id' => 'beauty_salons_sale',
                'name' => 'Салони за красота (Салоны красоты)',
                'template' => [
                    'path' => 'templates/olx/sales/beauty_salons_sale.csv',
                    'required_fields' => ['title', 'description', 'location_city', 'price_value', 'price_currency'],
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
                'id' => 'plots_rent',
                'name' => 'Парцели (Участки)',
                'template' => [
                    'path' => 'templates/olx/rent/plots_rent.csv',
                    'required_fields' => ['title', 'description', 'location_city', 'price_value', 'price_currency', 'space', 'power', 'water', 'reg'],
                ],
            ],
            [
                'id' => 'apartments_rent',
                'name' => 'Апартаменти (Квартиры)',
                'template' => [
                    'path' => 'templates/olx/rent/apartments_rent.csv',
                    'required_fields' => ['title', 'description', 'location_city', 'price_value', 'price_currency', 'heat', 'furn', 'pass', 'floors', 'floor', 'atype', 'space', 'cyear', 'ctype'],
                ],
            ],
            [
                'id' => 'offices_rent',
                'name' => 'Офиси (Офисы)',
                'template' => [
                    'path' => 'templates/olx/rent/offices_rent.csv',
                    'required_fields' => ['title', 'description', 'location_city', 'price_value', 'price_currency', 'heat', 'floors', 'floor', 'space', 'cyear', 'ctype'],
                ],
            ],
            [
                'id' => 'shops_rent',
                'name' => 'Магазини (Магазины)',
                'template' => [
                    'path' => 'templates/olx/rent/shops_rent.csv',
                    'required_fields' => ['title', 'description', 'location_city', 'price_value', 'price_currency', 'heat', 'floors', 'space', 'cyear', 'ctype', 'mestopolozhenie'],
                ],
            ],
            [
                'id' => 'garages_rent',
                'name' => 'Гаражи, паркоместа (Гаражи, парковочные места)',
                'template' => [
                    'path' => 'templates/olx/rent/garages_rent.csv',
                    'required_fields' => ['title', 'description', 'location_city', 'price_value', 'price_currency', 'space', 'power', 'water'],
                ],
            ],
            [
                'id' => 'houses_rent',
                'name' => 'Къщи, вили (Дома, виллы)',
                'template' => [
                    'path' => 'templates/olx/rent/houses_rent.csv',
                    'required_fields' => ['title', 'description', 'location_city', 'price_value', 'price_currency', 'heat', 'space', 'cyear', 'ctype', 'yspace', 'floors'],
                    'field_map' => [
                        'floors' => 'house_floors',
                    ],
                ],
            ],
            [
                'id' => 'warehouses_rent',
                'name' => 'Складове (Склады)',
                'template' => [
                    'path' => 'templates/olx/rent/warehouses_rent.csv',
                    'required_fields' => ['title', 'description', 'location_city', 'price_value', 'price_currency', 'heat', 'space', 'cyear', 'ctype'],
                ],
            ],
            [
                'id' => 'agricultural_rent',
                'name' => 'Земеделски имоти (Сельскохозяйственные объекты)',
                'template' => [
                    'path' => 'templates/olx/rent/agricultural_rent.csv',
                    'required_fields' => ['title', 'description', 'location_city', 'price_value', 'price_currency', 'space', 'pact', 'cat', 'type'],
                ],
            ],
            [
                'id' => 'restaurants_rent',
                'name' => 'Заведения (Заведения)',
                'template' => [
                    'path' => 'templates/olx/rent/restaurants_rent.csv',
                    'required_fields' => ['title', 'description', 'location_city', 'price_value', 'price_currency', 'heat', 'space', 'cyear', 'ctype'],
                ],
            ],
            [
                'id' => 'hotels_rent',
                'name' => 'Хотели (Гостиницы)',
                'template' => [
                    'path' => 'templates/olx/rent/hotels_rent.csv',
                    'required_fields' => ['title', 'description', 'location_city', 'price_value', 'price_currency', 'heat', 'floors', 'space', 'cyear', 'ctype'],
                ],
            ],
            [
                'id' => 'floor_rent',
                'name' => 'Етаж от къща (Этаж дома)',
                'template' => [
                    'path' => 'templates/olx/rent/floor_rent.csv',
                    'required_fields' => ['title', 'description', 'location_city', 'price_value', 'price_currency', 'heat', 'space', 'cyear', 'ctype', 'floors'],
                ],
            ],
            [
                'id' => 'industrial_rent',
                'name' => 'Промишлени сгради (Промышленные здания)',
                'template' => [
                    'path' => 'templates/olx/rent/industrial_rent.csv',
                    'required_fields' => ['title', 'description', 'location_city', 'price_value', 'price_currency', 'heat', 'floors', 'space', 'cyear', 'ctype'],
                ],
            ],
            [
                'id' => 'dance_halls_rent',
                'name' => 'Зали за танци, събития (Танцевальные залы, мероприятия)',
                'template' => [
                    'path' => 'templates/olx/rent/dance_halls_rent.csv',
                    'required_fields' => ['title', 'description', 'location_city', 'price_value', 'price_currency', 'vid', 'price_type'],
                ],
            ],
            [
                'id' => 'beauty_salons_rent',
                'name' => 'Салони за красота (Салоны красоты)',
                'template' => [
                    'path' => 'templates/olx/rent/beauty_salons_rent.csv',
                    'required_fields' => ['title', 'description', 'location_city', 'price_value', 'price_currency'],
                ],
            ],
        ],
    ],
];