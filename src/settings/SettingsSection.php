<?php declare(strict_types=1);
namespace Logto\WpPlugin\Settings;

class SettingsSection
{
  protected $rendered = false;

  public function __construct(
    public string $page,
    public string $optionName,
    public string $id,
    public string $title,
    public ?string $description,
  ) {
  }

  public function render(): void
  {
    if ($this->rendered) {
      return;
    }

    add_settings_section(
      $this->id,
      $this->title,
      fn() => $this->description ? print ("<p class='subtitle'>{$this->description}</p>") : null,
      $this->page,
    );
    $this->rendered = true;
  }

  public function addInputField(
    string $id,
    string $title,
    string $description,
    ?string $value,
    string $type = 'text',
  ): void {
    if (!$this->rendered) {
      throw new \Exception('Settings section must be rendered before adding input fields');
    }

    add_settings_field(
      $id,
      $title,
      fn() => $this->renderInputField($id, $type, $description, $value ?? ''),
      $this->page,
      $this->id,
    );
  }

  protected function renderInputField(string $id, string $type, string $description, string $value): void
  {
    echo "<input type='$type' id='$id' name='{$this->optionName}[$id]' value='$value' style='width: 300px;' />";
    echo "<p class='description'>$description</p>";
  }

  public function addSwitchField(
    string $id,
    string $title,
    ?string $label,
    string $description,
    bool $value,
  ): void {
    if (!$this->rendered) {
      throw new \Exception('Settings section must be rendered before adding input fields');
    }

    add_settings_field(
      $id,
      $title,
      fn() => $this->renderSwitchField($id, $label, $description, $value),
      $this->page,
      $this->id,
    );
  }

  protected function renderSwitchField(string $id, ?string $label, string $description, bool $value): void
  {
    echo "<input type='checkbox' id='$id' name='{$this->optionName}[$id]' " . checked($value, true, false) . " />";
    if ($label) {
      echo "<label for='$id'>$label</label>";
    }
    echo "<p class='description'>$description</p>";
  }

  public function addRadioField(
    string $id,
    string $title,
    string $description,
    string $value,
    array $options,
  ): void {
    if (!$this->rendered) {
      throw new \Exception('Settings section must be rendered before adding input fields');
    }

    add_settings_field(
      $id,
      $title,
      fn() => $this->renderRadioField($id, $description, $value, $options),
      $this->page,
      $this->id,
    );
  }

  protected function renderRadioField(string $id, string $description, string $value, array $options): void
  {
    echo "<div class='radio-group'>";
    foreach ($options as $optionValue => $optionLabel) {
      echo "<div>";
      echo "<input type='radio' id='$id-$optionValue' name='{$this->optionName}[$id]' value='$optionValue' " . checked($value, $optionValue, false) . " />";
      echo "<label for='$id-$optionValue'>$optionLabel</label>";
      echo "</div>";
    }
    echo "</div>";
    echo "<p class='description'>$description</p>";
  }

  public function addKeyValuePairsField(
    string $id,
    string $title,
    string $description,
    array $value,
    array $options = [],
  ): void {
    if (!$this->rendered) {
      throw new \Exception('Settings section must be rendered before adding input fields');
    }

    add_settings_field(
      $id,
      $title,
      fn() => $this->renderKeyValuePairsField($id, $description, $value, $options),
      $this->page,
      $this->id,
    );
  }

  protected function renderKeyValuePairsField(string $id, string $description, array $value, array $options): void
  {
    echo "<div id='edit-$id'>";

    foreach ($value as [$key, $val]) {
      echo "<div>";
      $this->renderKeyValuePair($id, $key, $val, $options);
      echo "</div>";
    }
    echo "</div>";
    echo "<button id='add-$id' type='button' class='button'>Add</button>";
    echo "<p class='description'>$description</p>";

    ?>
    <script>
      document.addEventListener('DOMContentLoaded', function () {
        const addBtn = document.getElementById('add-<?= $id ?>');
        const editDiv = document.getElementById('edit-<?= $id ?>');

        addBtn.addEventListener('click', function () {
          const div = document.createElement('div');
          div.innerHTML = `<?= $this->renderKeyValuePair($id, '', '', $options) ?>`;
          editDiv.appendChild(div);
        });

        editDiv.addEventListener('click', function (e) {
          if (e.target.tagName === 'BUTTON') {
            e.target.parentElement.remove();
          }
        });
      });
    </script>
    <?php
  }

  protected function renderKeyValuePair(string $id, string $key, string $val, array $options): void
  {
    echo "<input type='text' name='{$this->optionName}[$id][keys][]' value='$key' style='width: 150px;' placeholder='" . ($options['keyPlaceholder'] ?? 'Key') . "' />";
    echo "<input type='text' name='{$this->optionName}[$id][values][]' value='$val' style='width: 150px;' placeholder='" . ($options['valuePlaceholder'] ?? 'Value') . "' />";
    echo "<button type='button' class='button'>Delete</button>";
  }
}
