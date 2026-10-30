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
      $this->fillCountryOfParticipant($participant['id'], $participant['contact_id']);
    }
  }

  public function fillCountryOfParticipant(int $participantId, int $contactId) {
    $country = $this->getCountryOfContact($contactId);
    if (!empty($country['country_id'])) {
      \Civi\Api4\Participant::update(FALSE)
        ->addValue('Participant_Country.Country', $country['country_id'])
        ->addValue('Participant_Country.Relationship', $country['relationship'])
        ->addWhere('id', '=', $participantId)
        ->addWhere('Participant_Country.Country', 'IS EMPTY')
        ->execute();
    }
  }

  public function getCountryOfContact(int $contactId): ?array {
    // The country of a contact can be derived from:
    //   - the delegation = highest precedence
    //   - or the committee
    //   - or the mailing list = lowest precedence

    $country = $this->getCountryOfDelegationMember($contactId);
    if ($country) {
      return $country;
    }

    $country = $this->getCountryOfCommitteeMember($contactId);
    if ($country) {
      return $country;
    }

    $country = $this->getCountryOfMailingListMember($contactId);
    if ($country) {
      return $country;
    }

    return NULL;
  }

  private function getCountryOfDelegationMember(int $contactId): ?array {
    $relTypes = [
      13, // delegation member
      11, // head of delegation
      12, // information officer
      16, // Brussels Representative
      14, // Associate Representative
      15, // Observer Representative
      27, // staff member
    ];

    $relationship = \Civi\Api4\Relationship::get(FALSE)
      ->addSelect('contact_id_b', 'contact_id_b.display_name', 'relationship_type_id:label')
      ->addWhere('relationship_type_id', 'IN', $relTypes)
      ->addWhere('contact_id_a', '=', $contactId)
      ->addWhere('is_active', '=', TRUE)
      ->execute()
      ->first();
    if ($relationship) {
      return [
        'country_id' => $relationship['contact_id_b'],
        'relationship' => $relationship['relationship_type_id:label'] . ' ' . $relationship['contact_id_b.display_name'],
      ];
    }

    return NULL;
  }

  private function getCountryOfCommitteeMember(int $contactId): ?array {
    $relTypes = [
      25, // coordinator
      24, // member
      20, // chair
      21, // vice-chair
    ];

    $relationship = \Civi\Api4\Relationship::get(FALSE)
      ->addSelect('Committee_or_Network_Relationship_Details.Country.id', 'contact_id_b.display_name', 'relationship_type_id:label')
      ->addWhere('relationship_type_id', 'IN', $relTypes)
      ->addWhere('contact_id_a', '=', $contactId)
      ->addWhere('is_active', '=', TRUE)
      ->execute()
      ->first();
    if ($relationship) {
      return [
        'country_id' => $relationship['Committee_or_Network_Relationship_Details.Country.id'],
        'relationship' => $relationship['relationship_type_id:label'] . ' ' . $relationship['contact_id_b.display_name'],
      ];
    }

    return NULL;
  }

  private function getCountryOfMailingListMember(int $contactId): ?array {
    // identical to getCountryOfCommitteeMember (apart from relationship_type_id),
    // but this is a separate function because committee member has a higher precedence than mailing list recipient

    $relTypes = [
      23, // mailing recipient
    ];

    $relationship = \Civi\Api4\Relationship::get(FALSE)
      ->addSelect('Committee_or_Network_Relationship_Details.Country.id', 'contact_id_b.display_name', 'relationship_type_id:label')
      ->addWhere('relationship_type_id', 'IN', $relTypes)
      ->addWhere('contact_id_a', '=', $contactId)
      ->addWhere('is_active', '=', TRUE)
      ->execute()
      ->first();
    if ($relationship) {
      return [
        'country_id' => $relationship['Committee_or_Network_Relationship_Details.Country.id'],
        'relationship' => $relationship['relationship_type_id:label'] . ' ' . $relationship['contact_id_b.display_name'],
      ];

    }

    return NULL;
  }
}