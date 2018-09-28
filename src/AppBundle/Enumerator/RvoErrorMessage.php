<?php


namespace AppBundle\Enumerator;


class RvoErrorMessage
{
    const REPEATED_ARRIVAL_00015_MAIN_PART = 'Deze melding is al gedaan of er is niets gewijzigd.';
    const REPEATED_ARRIVAL_00042 = 'Er is al een aanvoermelding geregistreerd.';
    const REPEATED_DEPART_00184 = 'Herhaalde melding voor datum gebeurtenis, eerst melding verwijderen.';
    const REPEATED_LOSS_00185 = 'Herhaalde melding op of na datum gebeurtenis, eerst melding verwijderen.';
    const REPEATED_REVOKE_00309 = 'Herstelde of ingetrokken meldingen kunnen niet ingetrokken worden.';
}