@javascript
Feature: Associate a product
  In order to create associations between products and groups
  As a product manager
  I need to associate a product to other products and groups

  Background:
    Given a "footwear" catalog configuration
    And the following products:
      | sku            |
      | charcoal-boots |
      | black-boots    |
      | gray-boots     |
      | brown-boots    |
      | green-boots    |
      | shoelaces      |
      | glossy-boots   |
    And I am logged in as "Julia"

  Scenario: Associate a product to another product
    Given I edit the "charcoal-boots" product
    When I visit the "Associations" tab
    And I select the "Cross sell" association
    And I check the row "shoelaces"
    And I save the product
    Then I should see the text "1 products and 0 groups"
    Then the row "shoelaces" should be checked

  @skip @jira https://akeneo.atlassian.net/browse/PIM-4670
  Scenario: Keep association selection between tabs
    Given I edit the "charcoal-boots" product
    When I visit the "Associations" tab
    And I select the "Cross sell" association
    And I check the row "gray-boots"
    And I check the row "black-boots"
    And I select the "Pack" association
    And I check the row "glossy-boots"
    And I select the "Substitution" association
    And I press the "Show groups" button
    And I check the row "similar_boots"
    And I visit the "Attributes" tab
    And I visit the "Associations" tab
    And I select the "Cross sell" association
    Then the row "gray-boots" should be checked
    And the row "black-boots" should be checked
    When I select the "Pack" association
    Then the row "glossy-boots" should be checked
    When I select the "Substitution" association
    And I press the "Show groups" button
    Then the row "similar_boots" should be checked
    When I save the product
    And I select the "Cross sell" association
    And I uncheck the rows "black-boots"
    And I select the "Upsell" association
    And I check the rows "shoelaces"
    And I check the rows "black-boots"
    And I press the "Show groups" button
    And I check the rows "caterpillar_boots"
    And I select the "Cross sell" association
    Then the row "caterpillar_boots" should not be checked
    And I press the "Show products" button
    Then the row "black-boots" should not be checked

  @jira https://akeneo.atlassian.net/browse/PIM-4668
  Scenario: Detect unsaved changes when modifying associations
    Given I edit the "charcoal-boots" product
    When I visit the "Associations" tab
    And I select the "Cross sell" association
    And I check the row "gray-boots"
    And I check the row "black-boots"
    Then I should see the text "There are unsaved changes."
    And I visit the "Attributes" tab
    Then I should see the text "There are unsaved changes."
    When I save the product
    Then I should not see the text "There are unsaved changes."
    When I visit the "Associations" tab
    And I select the "Cross sell" association
    And I uncheck the rows "black-boots"
    Then I should see the text "There are unsaved changes."
    And I check the rows "black-boots"
    # Wait for the fade-out of the message
    And I wait 1 seconds
    Then I should not see the text "There are unsaved changes."
