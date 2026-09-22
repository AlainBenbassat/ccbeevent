<?php

class CRM_Ccbeevent_CountryFiller {
  public function fillCountries(int $eventId) {
    // select the event participants without country
    $participants = \Civi\Api4\Participant::get(FALSE)
      ->addSelect('id', 'contact_id')
      ->addWhere('event_id', '=', $eventId)
      ->addWhere('Participant_Country.Country', 'IS EMPTY')
      ->execute();

    foreach ($participants as $participant) {
      $countryId = $this->getCountryOfContact($participant['contact_id']);
      if ($countryId) {
        $this->fillCountryOfParticipant($participant['id'], $countryId);
      }
    }
  }

  public function fillCountryOfParticipant(int $participantId, int $countryId) {
    \Civi\Api4\Participant::update(FALSE)
      ->addValue('Participant_Country.Country', $countryId)
      ->addWhere('id', '=', $participantId)
      ->execute();
  }

  public function getCountryOfContact(int $contactId): ?int {
    // The country of a contact can be derived from:
    //   - the delegation = highest precedence
    //   - or the committee
    //   - or the mailing list = lowest precedence

    $countryId = $this->getCountryOfDelegationMember($contactId);
    if ($countryId) {
      return $countryId;
    }

    $countryId = $this->getCountryOfCommitteeMember($contactId);
    if ($countryId) {
      return $countryId;
    }

    $countryId = $this->getCountryOfMailingListMember($contactId);
    if ($countryId) {
      return $countryId;
    }

    return NULL;
  }

  private function getCountryOfDelegationMember(int $contactId): ?int {
    $relTypes = [
      13, // delegation member
      11, // head of delegation
      12, // information officer
      16, // Brussels Representative
      14, // Associate Representative
      15, // Observer Representative
    ];

    $relationship = \Civi\Api4\Relationship::get(FALSE)
      ->addSelect('contact_id_b')
      ->addWhere('relationship_type_id', 'IN', $relTypes)
      ->addWhere('contact_id_a', '=', $contactId)
      ->addWhere('is_active', '=', TRUE)
      ->execute()
      ->first();
    if ($relationship) {
      return $relationship['contact_id_b'];
    }

    return NULL;
  }

  private function getCountryOfCommitteeMember(int $contactId): ?int {
    $relTypes = [
      25, // coordinator
      24, // member
      20, // chair
      21, // vice-chair
    ];

    $relationship = \Civi\Api4\Relationship::get(FALSE)
      ->addSelect('Committee_or_Network_Relationship_Details.Country.id')
      ->addWhere('relationship_type_id', 'IN', $relTypes)
      ->addWhere('contact_id_a', '=', $contactId)
      ->addWhere('is_active', '=', TRUE)
      ->execute()
      ->first();
    if ($relationship) {
      return $relationship['Committee_or_Network_Relationship_Details.Country.id'];
    }

    return NULL;
  }

  private function getCountryOfMailingListMember(int $contactId): ?int {
    // identical to getCountryOfCommitteeMember (apart from relationship_type_id),
    // but this is a separate function because committee member has a higher precedence than mailing list recipient

    $relTypes = [
      25, // mailing recipient
    ];

    $relationship = \Civi\Api4\Relationship::get(FALSE)
      ->addSelect('Committee_or_Network_Relationship_Details.Country.id')
      ->addWhere('relationship_type_id', 'IN', $relTypes)
      ->addWhere('contact_id_a', '=', $contactId)
      ->addWhere('is_active', '=', TRUE)
      ->execute()
      ->first();
    if ($relationship) {
      return $relationship['Committee_or_Network_Relationship_Details.Country.id'];
    }

    return NULL;
  }
}