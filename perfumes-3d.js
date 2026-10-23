import * as THREE from "three";
import { GLTFLoader } from "three/addons/loaders/GLTFLoader.js";

const host = document.querySelector("[data-perfume-3d]");

if (host) {
  const canvas = host.querySelector("canvas");
  const fallback = host.querySelector(".respaldo-perfumes");
  const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  let renderer;
  let frameId = 0;
  let pointerDown = false;
  let lastX = 0;
  let dragRotation = 0;
  const clock = new THREE.Clock();
  const animatedBottles = [];
  const loader = new GLTFLoader();

  try {
    renderer = new THREE.WebGLRenderer({ canvas, alpha: true, antialias: true });
    renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
    renderer.setSize(Math.max(host.clientWidth, 1), Math.max(host.clientHeight, 1));
    renderer.outputColorSpace = THREE.SRGBColorSpace;
    renderer.toneMapping = THREE.ACESFilmicToneMapping;
    renderer.toneMappingExposure = 1.15;

    const scene = new THREE.Scene();
    const camera = new THREE.PerspectiveCamera(35, host.clientWidth / Math.max(host.clientHeight, 1), 0.1, 100);
    camera.position.set(0, 0.35, 8.2);

    const stage = new THREE.Group();
    scene.add(stage);

    scene.add(new THREE.HemisphereLight(0xfff4ef, 0x4b2938, 2.1));
    const keyLight = new THREE.DirectionalLight(0xfff8ee, 3.4);
    keyLight.position.set(-3.5, 4.5, 5);
    scene.add(keyLight);
    const fillLight = new THREE.DirectionalLight(0xffc9d8, 2.1);
    fillLight.position.set(4, 1.5, 3);
    scene.add(fillLight);
    const rimLight = new THREE.DirectionalLight(0xd6aa61, 2.6);
    rimLight.position.set(1.5, 3, -3);
    scene.add(rimLight);

    // Fondo transparente: la escena se integra directamente con el rosado de la portada.

    const gold = new THREE.MeshStandardMaterial({ color: 0xc6a15e, metalness: 0.86, roughness: 0.2 });
    const bottleGlass = new THREE.MeshPhysicalMaterial({
      color: 0xf9e7ed, metalness: 0.04, roughness: 0.13,
      transmission: 0.28, transparent: true, opacity: 0.78,
      thickness: 0.4, clearcoat: 1, clearcoatRoughness: 0.08
    });
    const liquids = [0x9a3f5e, 0xd5a64d, 0x554047];
    const caps = [0x2c252a, 0xd1ad6b, 0x343039];

    function makeLabelTexture(index) {
      const labelCanvas = document.createElement("canvas");
      labelCanvas.width = 512;
      labelCanvas.height = 320;
      const c = labelCanvas.getContext("2d");
      c.fillStyle = ["#f6eee5", "#30252b", "#f4e8dc"][index];
      c.fillRect(0, 0, 512, 320);
      c.strokeStyle = "#c6a15e";
      c.lineWidth = 7;
      c.strokeRect(18, 18, 476, 284);
      c.textAlign = "center";
      c.fillStyle = index === 1 ? "#f7e6ca" : "#51323e";
      c.font = "bold 34px Georgia";
      c.fillText("AROMA LUXE", 256, 116);
      c.font = "24px Georgia";
      c.fillText(["ROSE ÉTERNELLE", "OR NOIR", "VELVET AMBER"][index], 256, 172);
      c.font = "18px Georgia";
      c.fillText("EAU DE PARFUM", 256, 222);
      const texture = new THREE.CanvasTexture(labelCanvas);
      texture.colorSpace = THREE.SRGBColorSpace;
      return texture;
    }

    function roundedShape(width, height, radius) {
      const x = -width / 2;
      const y = -height / 2;
      const shape = new THREE.Shape();
      shape.moveTo(x + radius, y);
      shape.lineTo(x + width - radius, y);
      shape.quadraticCurveTo(x + width, y, x + width, y + radius);
      shape.lineTo(x + width, y + height - radius);
      shape.quadraticCurveTo(x + width, y + height, x + width - radius, y + height);
      shape.lineTo(x + radius, y + height);
      shape.quadraticCurveTo(x, y + height, x, y + height - radius);
      shape.lineTo(x, y + radius);
      shape.quadraticCurveTo(x, y, x + radius, y);
      return shape;
    }

    function createFallbackBottle(index) {
      const bottle = new THREE.Group();
      const wide = index === 1 ? 1.02 : 0.88;
      const tall = index === 1 ? 1.9 : 1.58;
      const depth = index === 1 ? 0.42 : 0.36;

      // Cuerpo de vidrio moldeado, con esquinas redondeadas y paredes brillantes.
      const bodyShape = roundedShape(wide, tall, 0.19);
      const bodyGeometry = new THREE.ExtrudeGeometry(bodyShape, {
        depth, bevelEnabled: true, bevelSegments: 6,
        steps: 1, bevelSize: 0.095, bevelThickness: 0.09, curveSegments: 16
      });
      bodyGeometry.center();
      const glass = new THREE.MeshPhysicalMaterial({
        color: [0xf7e6eb, 0xf7ead0, 0xe9dce0][index],
        metalness: 0.02, roughness: 0.075, transmission: 0.42,
        transparent: true, opacity: 0.48, thickness: 0.65,
        ior: 1.46, clearcoat: 1, clearcoatRoughness: 0.035,
        envMapIntensity: 1.25, side: THREE.DoubleSide
      });
      bottle.add(new THREE.Mesh(bodyGeometry, glass));

      // Perfume líquido con hombros suaves y tono profundo; queda dentro del vidrio.
      const liquidShape = roundedShape(wide * 0.77, tall * 0.62, 0.11);
      const liquidGeometry = new THREE.ExtrudeGeometry(liquidShape, {
        depth: depth * 0.49, bevelEnabled: true, bevelSegments: 3,
        bevelSize: 0.025, bevelThickness: 0.025, curveSegments: 12
      });
      liquidGeometry.center();
      const liquid = new THREE.Mesh(liquidGeometry, new THREE.MeshPhysicalMaterial({
        color: liquids[index], metalness: 0.12, roughness: 0.13,
        clearcoat: 1, clearcoatRoughness: 0.04, transmission: 0.04
      }));
      liquid.position.set(0, -0.2, 0.015);
      bottle.add(liquid);

      // Cuello, anillos metálicos y atomizador.
      const neckY = tall / 2 + 0.12;
      const neck = new THREE.Mesh(
        new THREE.CylinderGeometry(0.17, 0.205, 0.22, 48, 1),
        new THREE.MeshStandardMaterial({ color: 0xd8bd85, metalness: 0.94, roughness: 0.16 })
      );
      neck.position.y = neckY;
      bottle.add(neck);
      const collar = new THREE.Mesh(
        new THREE.CylinderGeometry(0.235, 0.235, 0.075, 48),
        new THREE.MeshStandardMaterial({ color: 0xb68a43, metalness: 0.96, roughness: 0.13 })
      );
      collar.position.y = tall / 2 + 0.235;
      bottle.add(collar);

      const capMaterial = new THREE.MeshStandardMaterial({
        color: caps[index], metalness: index === 1 ? 0.78 : 0.56,
        roughness: index === 1 ? 0.17 : 0.24
      });
      const capRadius = index === 1 ? 0.275 : 0.235;
      const capHeight = index === 1 ? 0.43 : 0.37;
      const cap = new THREE.Mesh(
        new THREE.CylinderGeometry(capRadius * 0.96, capRadius, capHeight, 48, 1),
        capMaterial
      );
      cap.position.y = tall / 2 + 0.47;
      bottle.add(cap);
      const capTop = new THREE.Mesh(
        new THREE.CylinderGeometry(capRadius * 0.9, capRadius * 0.96, 0.035, 48),
        new THREE.MeshStandardMaterial({ color: index === 1 ? 0xe4c98f : caps[index], metalness: 0.85, roughness: 0.18 })
      );
      capTop.position.y = cap.position.y + capHeight / 2;
      bottle.add(capTop);

      // Etiqueta con marco fino y placa física para que parezca impresa en el frasco.
      const labelWidth = wide * 0.76;
      const labelHeight = tall * 0.3;
      const labelBacking = new THREE.Mesh(
        new THREE.BoxGeometry(labelWidth + 0.035, labelHeight + 0.035, 0.018),
        new THREE.MeshStandardMaterial({ color: index === 1 ? 0x30252b : 0xf6eee5, metalness: 0.18, roughness: 0.38 })
      );
      labelBacking.position.set(0, -0.01, depth / 2 + 0.072);
      bottle.add(labelBacking);
      const label = new THREE.Mesh(
        new THREE.PlaneGeometry(labelWidth, labelHeight),
        new THREE.MeshStandardMaterial({ map: makeLabelTexture(index), roughness: 0.4, metalness: 0.12 })
      );
      label.position.set(0, -0.01, depth / 2 + 0.083);
      bottle.add(label);

      // Reflejos verticales largos: simulan las líneas de luz de un estudio fotográfico.
      const reflectionMaterial = new THREE.MeshBasicMaterial({
        color: 0xffffff, transparent: true, opacity: 0.48,
        blending: THREE.AdditiveBlending, depthWrite: false
      });
      [-1, 1].forEach((side) => {
        const highlight = new THREE.Mesh(
          new THREE.BoxGeometry(0.025, tall * 0.72, 0.012),
          reflectionMaterial.clone()
        );
        highlight.position.set(side * (wide * 0.405), 0, depth / 2 + 0.105);
        highlight.rotation.z = side * -0.025;
        bottle.add(highlight);
      });

      const baseRim = new THREE.Mesh(
        new THREE.BoxGeometry(wide * 0.78, 0.035, 0.025),
        new THREE.MeshStandardMaterial({ color: 0xd4b477, metalness: 0.9, roughness: 0.2 })
      );
      baseRim.position.set(0, -tall * 0.43, depth / 2 + 0.06);
      bottle.add(baseRim);
      return bottle;
    }

    const placements = [
      { x: -1.72, y: -0.14, z: 0.05, scale: 0.79, yaw: -0.16, path: "model_assets/small_bottle.glb", fallback: 0 },
      { x: 0, y: -0.03, z: 0.25, scale: 1.03, yaw: 0.22, path: "model_assets/large_bottle.glb", fallback: 1 },
      { x: 1.7, y: -0.17, z: -0.02, scale: 0.77, yaw: 0.16, path: "model_assets/small_bottle.glb", fallback: 2 }
    ];

    function fitModel(object, maxSize) {
      const bounds = new THREE.Box3().setFromObject(object);
      const size = new THREE.Vector3();
      const center = new THREE.Vector3();
      bounds.getSize(size);
      bounds.getCenter(center);
      object.position.sub(center);
      const largest = Math.max(size.x, size.y, size.z, 0.001);
      object.scale.multiplyScalar(maxSize / largest);
    }

    function addBottle(object, placement, index) {
      fitModel(object, index === 1 ? 2.95 : 2.35);
      object.position.set(placement.x, placement.y, placement.z);
      object.scale.multiplyScalar(placement.scale);
      object.rotation.y = placement.yaw;
      object.userData.baseY = placement.y;
      object.userData.phase = index * 1.7;
      object.userData.baseRotation = placement.yaw;
      stage.add(object);
      animatedBottles.push(object);
    }

    // Se muestran frascos 3D propios inmediatamente; los GLB gratuitos pueden reemplazarlos.
    placements.forEach((placement, index) => {
      const fallbackBottle = createFallbackBottle(index);
      addBottle(fallbackBottle, placement, index);

      loader.load(
        placement.path,
        (gltf) => {
          const current = animatedBottles.find((item) => item.userData.phase === index * 1.7);
          if (current) {
            stage.remove(current);
            animatedBottles.splice(animatedBottles.indexOf(current), 1);
          }
          addBottle(gltf.scene, placement, index);
        },
        undefined,
        () => {
          // Si los archivos GLB aún no están subidos, permanece el frasco 3D de respaldo.
        }
      );
    });

    // Conserva las fotos visibles hasta que el primer render 3D termine correctamente.
    // Si el navegador no puede dibujar la escena, las imágenes de respaldo permanecen visibles.
    let firstRenderCompleted = false;

    function resize() {
      const width = Math.max(host.clientWidth, 1);
      const height = Math.max(host.clientHeight, 1);
      camera.aspect = width / height;
      camera.updateProjectionMatrix();
      renderer.setSize(width, height);
    }
    const observer = new ResizeObserver(resize);
    observer.observe(host);

    canvas.addEventListener("pointerdown", (event) => {
      pointerDown = true;
      lastX = event.clientX;
      canvas.setPointerCapture(event.pointerId);
    });
    canvas.addEventListener("pointermove", (event) => {
      if (!pointerDown) return;
      dragRotation += (event.clientX - lastX) * 0.008;
      lastX = event.clientX;
    });
    const releasePointer = () => { pointerDown = false; };
    canvas.addEventListener("pointerup", releasePointer);
    canvas.addEventListener("pointercancel", releasePointer);

    function animate() {
      frameId = requestAnimationFrame(animate);
      const time = clock.getElapsedTime();
      animatedBottles.forEach((bottle) => {
        if (!reduceMotion) bottle.position.y = bottle.userData.baseY + Math.sin(time * 1.05 + bottle.userData.phase) * 0.095;
        bottle.rotation.y = bottle.userData.baseRotation + dragRotation + (reduceMotion ? 0 : Math.sin(time * 0.34 + bottle.userData.phase) * 0.075);
      });
      renderer.render(scene, camera);
      if (!firstRenderCompleted) {
        firstRenderCompleted = true;
        host.classList.add("escena-3d--activa");
        if (fallback) fallback.setAttribute("aria-hidden", "true");
      }
    }
    animate();

    window.addEventListener("pagehide", () => {
      cancelAnimationFrame(frameId);
      observer.disconnect();
      renderer.dispose();
      backdropTexture.dispose();
    }, { once: true });
  } catch (error) {
    console.error("No se pudo iniciar la escena 3D de Aroma Luxe:", error);
    host.classList.remove("escena-3d--activa");
  }
}
