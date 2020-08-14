<?php

namespace AppBundle\Enumerator;

use AppBundle\Traits\EnumInfo;

class DebtorNumberCompanyType
{
    use EnumInfo;
    
    const HOLDER_I_R = 'HIR';
    const HOLDER_PEDIGREE_BREEDING = 'HSF';
    const HOLDER_SLAUGHTER = 'HSL';
}