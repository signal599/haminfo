<?php

declare(strict_types=1);

namespace Drush\Commands\radix;

use Consolidation\AnnotatedCommand\CommandData;
use Consolidation\AnnotatedCommand\CommandError;
use Drush\Commands\DrushCommands;
use Drush\Drush;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

/**
 * Class SubThemeCommands handles Radix subtheme creation.
 */
class SubThemeCommands extends DrushCommands {

  /**
   * Creates a Radix sub-theme.
   *
   * @command radix:create
   * @aliases radix
   * @param string $name The machine-readable name of your sub-theme.
   * @bootstrap full
   *
   * @usage drush radix:create 'My Theme'
   *   Creates a Radix sub-theme called "My Theme", using the radix_starterkit options.
   * @usage drush radix:create 'My Theme' --machine_name=my_theme
   *   Creates a Radix sub-theme called "My Theme" with a specific machine name.
   */
  public function createSubTheme(string $name) {
    try {
      $this->copyStarterKit();
      $this->generateTheme($name);
      $this->copyDotFiles($name);
      $this->removeCopiedStarterKit();

      // Success message.
      $this->logger()->success("🚀 Sub-theme '{$name}' created successfully. You may now enable it in the Appearance section of the Drupal administration or by Drush as shown below:");

      $this->printHeading(PHP_EOL . "RADIX DOCUMENTATION");
      $this->logger()->notice("Read the Radix comprehensive documentation or tl;dr below:");
      $this->printCommand('https://radix.trydrupal.com');

      $this->printHeading("🟡 STEP 1");
      $this->logger()->notice("Enable and set {$name} as the default theme:");
      $this->printCommand("drush then {$name} -y");
      $this->printCommand("drush config-set system.theme default {$name} -y");

      $this->printHeading("🟡 STEP 2");
      $this->logger()->notice("Go to the root of the {$name} theme:");
      $this->printCommand("cd web/themes/custom/{$name}");

      $this->printHeading("🟡 STEP 3");
      $this->logger()->notice("Set the theme to use the correct Node version via nvm:");
      $this->printCommand('nvm use');

      $this->printHeading("🟡 STEP 4");
      $this->logger()->notice("Install the required node packages:");
      $this->printCommand('npm install');

      $this->printHeading("🟡 STEP 5");
      $this->logger()->notice("Create a copy of <fg=green>.env.example</> file and rename it to <fg=green>.env.local</>:");
      $this->printCommand('cp .env.example .env.local');

      $this->printHeading("🟡 STEP 6");
      $this->logger()->notice("Update <fg=green>DRUPAL_BASE_URL</> in newly created <fg=green>.env.local</> file to match your local environment URL.");

      $this->printHeading("✅ STEP 7");
      $this->logger()->notice("Run the following command to compile Sass, JS and watch for other changes:");
      $this->printCommand('npm run watch');
    } catch (\Exception $exception) {
      $this->logger()->error($exception->getMessage());
    }
  }

  /**
   * Function to print command.
   *
   * @param string $command
   *   Hold command to be print.
   */
  private function printCommand(string $command) {
    $formattedCommand = "<fg=green>$command</>";
    $this->output()->writeln($formattedCommand);
  }

  /**
   * Function to print heading.
   *
   * @param string $heading
   *   Hold heading data to be print.
   */
  private function printHeading(string $heading) {
    $formattedHeading = PHP_EOL . "<options=bold>$heading:</>";
    $this->output()->writeln($formattedHeading);
  }

  /**
   * Function to copy starterkit components.
   */
  private function copyStarterKit() {
    $filesystem = new Filesystem();
    $drupalRoot = Drush::bootstrapManager()->getRoot();
    $source = $drupalRoot . '/themes/contrib/radix/src/kits/radix_starterkit';
    $destination = $drupalRoot . '/themes/custom/radix_starterkit';
    $filesystem->mirror($source, $destination);
  }

  /**
   * Temporary function to copy the dot files.
   * @see: https://www.drupal.org/project/drupal/issues/3456699
   */
  private function copyDotFiles(string $themeName) {
    $filesystem = new Filesystem();
    $drupalRoot = Drush::bootstrapManager()->getRoot();
    $source = $drupalRoot . '/themes/contrib/radix/src/kits/radix_starterkit';
    $destination = $drupalRoot . '/themes/custom/' . $themeName;
  
    $dotFiles = ['.gitignore', '.nvmrc', '.env.example', '.browserslistrc', '.stylelintrc.json', '.npmrc', '.stylelintignore', '.gitkeep'];
    foreach ($dotFiles as $file) {
      if (file_exists("$source/$file")) {
        $filesystem->copy("$source/$file", "$destination/$file");
      }
    }
  }

  /**
   * Function to generate theme.
   *
   * @param string $themeName
   *   Holds theme name to generate.
   */
  private function generateTheme(string $themeName) {
    $drupalRoot = Drush::bootstrapManager()->getRoot();
    $process = new Process([
      'php', $drupalRoot . '/core/scripts/drupal', 'generate-theme', '--starterkit', 'radix_starterkit', $themeName, '--path', 'themes/custom',
    ]);
    $process->run();

    if (!$process->isSuccessful()) {
      throw new \RuntimeException($process->getErrorOutput());
    }
  }

  /**
   * Function to remove starterkit components.
   */
  private function removeCopiedStarterKit() {
    $filesystem = new Filesystem();
    $filesystem->remove('../web/themes/custom/radix_starterkit');
  }

  /**
   * Function to validate created subtheme.
   *
   * @hook validate radix:create
   */
  public function validateCreateSubTheme(CommandData $commandData): ?CommandError {
    $name = $commandData->input()->getArgument('name');
    if (!$this->isValidName($name)) {
      return new CommandError("Invalid theme name: '$name'. Name must be a non-empty string.");
    }
    return NULL;
  }

  /**
   * Function to check for valid name.
   *
   * @param string $name
   *   The subtheme name.
   */
  private function isValidName(string $name): bool {
    return !empty($name);
  }
}
