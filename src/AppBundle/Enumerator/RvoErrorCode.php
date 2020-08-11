<?php


namespace AppBundle\Enumerator;


class RvoErrorCode
{
    /*
     * AnimalFlag: repeated declare
     * Example: "Error message example: De vlag bestaat al bij het dier."
     */
    const REPEATED_ANIMAL_FLAG_01521 = 'IRD-01521';

    /*
     * Repeated declare for: Arrival, AnimalFlag
     * Example: "Error message example: Deze melding is al gedaan of er is niets gewijzigd.
     *           Aanvullende info: meldingnummer = 201346674, ander kanaal = N, andere melder = N."
     */
    const REPEATED_DECLARE_00015 = 'IRD-00015';

    /*
     * Arrival: repeated declare
     * Example: Er is al een aanvoermelding geregistreerd
     */
    const REPEATED_ARRIVAL_00042 = 'IRD-00042';

    /*
     * Depart: repeated declare
     * Example: Herhaalde melding voor datum gebeurtenis, eerst melding verwijderen
     */
    const REPEATED_DEPART_00184 = 'IRD-00184';

    /*
     * Loss: repeated declare
     * Example: Herhaalde melding op of na datum gebeurtenis, eerst melding verwijderen
     */
    const REPEATED_LOSS_00185 = 'IRD-00185';

    /*
     * Revoke: repeated declare
     * Example: Herstelde of ingetrokken meldingen kunnen niet ingetrokken worden.
     */
    const REPEATED_REVOKE_00309 = 'IRD-00309';
}