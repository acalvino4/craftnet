<?php
namespace craftnet\orgs;

/**
 * @method static self Owner()
 * @method static self Admin()
 * @method static self Member()
 */
final class MemberRoleEnum extends \Mabe\Enum\Cl\EmulatedStringEnum
{
    protected const Owner = 'owner';
    protected const Admin = 'admin';
    protected const Member = 'member';
}
