<?php

namespace Drupal\Tests\se_testing\Traits;

use Drupal\node\Entity\Node;
use Faker\Factory;

/**
 * Provides functions for creating content during functional tests.
 */
trait TimekeepingTestTrait {

  /**
   * Setup basic faker fields for this test trait.
   */
  public function timekeepingFakerSetup() {
    $this->faker = Factory::create();

    $original = error_reporting(0);
    $this->timekeeping->name          = $this->faker->text;
    $this->timekeeping->phoneNumber   = $this->faker->phoneNumber;
    $this->timekeeping->mobileNumber  = $this->faker->phoneNumber;
    $this->timekeeping->streetAddress = $this->faker->streetAddress;
    $this->timekeeping->suburb        = $this->faker->city;
    $this->timekeeping->state         = $this->faker->stateAbbr;
    $this->timekeeping->postcode      = $this->faker->postcode;
    $this->timekeeping->url           = $this->faker->url;
    $this->timekeeping->companyEmail  = $this->faker->companyEmail;
    error_reporting($original);
  }

  public function addTimekeeping() {
    /** @var Node $node */
    $node = $this->createNode([
      'type' => 'se_timekeeping',
      'title' => $this->timekeeping->name,
      'field_tk_phone' => $this->timekeeping->phoneNumber,
      'field_tk_email' => $this->timekeeping->companyEmail,
    ]);
    $this->assertNotEqual($node, FALSE);
    $this->drupalGet($node->toUrl());
    $this->assertSession()->statusCodeEquals(200);

    $content = $this->getTextContent();

    $this->assertNotContains('Please fill in this field', $content);

    // Check that what we entered is shown.
    $this->assertContains($this->timekeeping->name, $content);
    $this->assertContains($this->timekeeping->phoneNumber, $content);

    return $node;
  }


}