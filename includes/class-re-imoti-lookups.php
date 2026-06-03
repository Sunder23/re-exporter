<?php
/**
 * Static lookup tables for imoti.net controlled-vocabulary fields.
 *
 * Data sourced from the official imoti.net RSS API:
 *   getData.php?get=ad_type, buildings, constructions, currency, extras
 *
 * @package RE_Exporter
 */

namespace RE_Exporter;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Imoti_Lookups
 */
class Imoti_Lookups {

	/** @var array<int,string> OfferType codes */
	public static $offer_types = array(
		2 => 'продава',
		3 => 'дава под наем',
		4 => 'нощувки',
		5 => 'купува',
		6 => 'заменя',
		7 => 'търгове',
		8 => 'търси да наеме',
	);

	/** @var array<int,string> EstateType codes */
	public static $estate_types = array(
		5  => 'Едностаен апартамент',
		6  => 'Двустаен апартамент',
		9  => 'Тристаен апартамент',
		27 => 'Четиристаен апартамент',
		10 => 'Многостаен апартамент',
		8  => 'Мезонет',
		13 => 'Ателие, Таван, Студио',
		11 => 'Офис',
		12 => 'Стая',
		14 => 'Къща',
		15 => 'Етаж от къща',
		25 => 'Вила',
		26 => 'Сграда',
		16 => 'Парцел',
		17 => 'Магазин',
		18 => 'Заведение',
		19 => 'Склад',
		20 => 'Гараж, паркомясто',
		21 => 'Хотел',
		22 => 'Фабрика',
		23 => 'Промишлен имот',
		24 => 'Земеделски имот',
		28 => 'Гора',
		29 => 'Бензиностанция',
		30 => 'Сутерен, Мазе, Партер',
		31 => 'Язовир',
		32 => 'Търговски обект',
		33 => 'Зала',
		34 => 'Селскостопански имот/Ферма',
		35 => 'Сглобяема къща',
		36 => 'Проект',
		37 => 'Дом за здравни и социални грижи',
		38 => 'Плаж',
	);

	/** @var array<int,string> BuildType codes */
	public static $build_types = array(
		2  => 'Тухла',
		1  => 'ЕПК',
		3  => 'Панел',
		10 => 'IBT технология',
		4  => 'Гредоред',
		5  => 'Метална',
		9  => 'Масивна',
	);

	/** @var array<int,string> CurrencyID codes */
	public static $currencies = array(
		2 => 'BGN',
		4 => 'EUR',
	);

	/** @var array<int,string> Extras codes */
	public static $extras = array(
		35 => 'ТЕЦ',
		1  => 'Локал. отопление',
		11 => 'Необзаведен',
		12 => 'Обзаведен',
		20 => 'Регулация',
		21 => 'Климатик',
		22 => 'Обезопасен',
		24 => 'Офис сграда',
		28 => 'Интернет връзка',
		29 => 'Охрана',
		30 => 'Паркомясто',
		36 => 'Оборудване',
		37 => 'Товарен вход',
		38 => 'Гараж',
		32 => 'Ток',
		25 => 'ВИЗА',
		33 => 'Водопровод',
		34 => 'Канализация',
		39 => 'Кухня',
		40 => 'Склад',
		41 => 'WC',
		42 => 'Предназначение',
		44 => 'Равен',
		46 => 'Преден',
		47 => 'Заден',
		48 => 'Конферентна зала',
		49 => 'Ресторант',
		50 => 'Басейн',
		51 => 'Фитнес зала',
		52 => 'Трифазен ток',
		53 => 'Асансьор',
		54 => 'Двор',
		55 => 'За обезщетение',
		57 => 'За жилищно строителство',
		58 => 'За промишлено строителство',
		59 => 'Делим',
		60 => 'Ново строителство',
		61 => 'Разрешено за дом. любимец',
		62 => 'Подземен',
		63 => 'Надземен',
		65 => 'Фризьорски салон',
		66 => 'Козметичен салон',
		67 => 'Автомивка',
		68 => 'Фитнес център',
		69 => 'Клиника',
		70 => 'Кабинет',
		71 => 'Спа център',
		72 => 'Зала',
		73 => 'Достъпно за хора с увреждания',
		74 => 'Подходящо за живеене',
		75 => 'Лукс',
		76 => 'С басейн',
		77 => 'До метростанция - до 10 мин.пеш',
		78 => 'За бежанци',
		79 => 'На първа линия',
	);

	// =========================================================================
	// Helpers
	// =========================================================================

	/**
	 * Return the lookup array for a given field.
	 *
	 * @param string $field  imoti field name.
	 * @return array<int,string>
	 */
	public static function get( $field ) {
		switch ( $field ) {
			case 'OfferType':  return self::$offer_types;
			case 'EstateType': return self::$estate_types;
			case 'BuildType':  return self::$build_types;
			case 'CurrencyID': return self::$currencies;
			case 'Extras':     return self::$extras;
			default:           return array();
		}
	}

	/**
	 * Fields that use a dropdown (have a known, fixed lookup).
	 *
	 * @return string[]
	 */
	public static function dropdown_fields() {
		return array( 'OfferType', 'EstateType', 'BuildType', 'CurrencyID' );
	}

	/**
	 * Fields that use a plain text input (no fixed lookup — just a numeric ID).
	 *
	 * @return string[]
	 */
	public static function text_fields() {
		return array( 'EstateRegion', 'EstateSubRegion' );
	}

	/**
	 * All D4 fields (dropdowns + text inputs + extras).
	 *
	 * @return string[]
	 */
	public static function d4_fields() {
		return array( 'OfferType', 'EstateType', 'BuildType', 'CurrencyID', 'EstateRegion', 'EstateSubRegion', 'Extras' );
	}

	/**
	 * WordPress post meta key for storing the human-readable label alongside the numeric ID.
	 * Returns an empty string for fields that don't have a companion name meta key.
	 *
	 * @param string $field
	 * @return string
	 */
	public static function name_meta_key( $field ) {
		$map = array(
			'EstateRegion'    => '_re_imoti_estate_region_name',
			'EstateSubRegion' => '_re_imoti_estate_subregion_name',
		);
		return isset( $map[ $field ] ) ? $map[ $field ] : '';
	}

	/**
	 * WordPress post meta key for a given D4 field.
	 *
	 * @param string $field
	 * @return string
	 */
	public static function meta_key( $field ) {
		$map = array(
			'OfferType'      => '_re_imoti_offer_type',
			'EstateType'     => '_re_imoti_estate_type',
			'BuildType'      => '_re_imoti_build_type',
			'CurrencyID'     => '_re_imoti_currency_id',
			'EstateRegion'   => '_re_imoti_estate_region',
			'EstateSubRegion'=> '_re_imoti_estate_subregion',
			'Extras'         => '_re_imoti_extras',
		);
		return isset( $map[ $field ] ) ? $map[ $field ] : '_re_imoti_' . sanitize_key( $field );
	}

	/**
	 * Get the human-readable label for a stored value.
	 *
	 * @param string $field
	 * @param string $value  Stored value (numeric code or comma-separated).
	 * @return string
	 */
	public static function label( $field, $value ) {
		if ( '' === $value ) {
			return '';
		}

		if ( 'Extras' === $field ) {
			$ids    = array_filter( array_map( 'trim', explode( ',', $value ) ) );
			$labels = array();
			foreach ( $ids as $id ) {
				$labels[] = isset( self::$extras[ (int) $id ] )
					? self::$extras[ (int) $id ]
					: $id;
			}
			return implode( ', ', $labels );
		}

		$lookup = self::get( $field );
		$int    = (int) $value;
		return isset( $lookup[ $int ] ) ? $lookup[ $int ] : $value;
	}
}
