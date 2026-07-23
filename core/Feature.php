<?php

final class Feature
{
    public static function on(string $key): bool
    {
        return (new Setting())->getBool('feature_' . $key, true);
    }

    public static function trainerModuleOn(): bool
    {
        return self::on('trainer_module');
    }

    public static function trainerBookingOn(): bool
    {
        return self::trainerModuleOn() && self::on('trainer_booking');
    }

    public static function trainerFeeOn(): bool
    {
        return self::trainerModuleOn() && self::on('trainer_fee');
    }

    public static function trainerDisplayOn(): bool
    {
        return self::trainerModuleOn() && self::on('trainer_display');
    }
}
