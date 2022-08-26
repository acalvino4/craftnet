<?php
namespace craft\elements;

/**
 * @mixin \craft\commerce\behaviors\CustomerBehavior
 * @mixin \craftnet\behaviors\UserBehavior
 */
class User {}

namespace craft\commerce\models;

/**
 * @mixin \craftnet\behaviors\PaymentSourceBehavior
 */
class PaymentSource {}

namespace craft\commerce\behaviors;

class CustomerBehavior {}

namespace craftnet\behaviors;

class UserBehavior {}
