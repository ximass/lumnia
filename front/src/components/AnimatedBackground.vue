<template>
  <div class="animated-bg">
    <canvas ref="canvas" class="network-canvas"></canvas>
  </div>
</template>

<script lang="ts">
  import { defineComponent, ref, onMounted, onUnmounted } from 'vue'

  interface Point {
    x: number
    y: number
    vx: number
    vy: number
    connections: number[]
  }

  export default defineComponent({
    name: 'AnimatedBackground',
    setup() {
      const canvas = ref<HTMLCanvasElement | null>(null)
      
      let animationId: number
      let points: Point[] = []
      let ctx: CanvasRenderingContext2D | null = null

      // Configurações da animação
      const numPoints = 20
      const maxDistance = 200
      const speed = 2

      const initCanvas = () => {
        if (!canvas.value) return
        
        ctx = canvas.value.getContext('2d')
        if (!ctx) return

        // Configurar tamanho do canvas
        const resizeCanvas = () => {
          canvas.value!.width = window.innerWidth
          canvas.value!.height = window.innerHeight
        }
        
        resizeCanvas()
        window.addEventListener('resize', resizeCanvas)

        // Criar pontos iniciais
        createPoints()
        
        // Iniciar animação
        animate()
      }

      const createPoints = () => {
        points = []
        const centerX = window.innerWidth / 2
        const centerY = window.innerHeight / 2
        const radius = 350

        for (let i = 0; i < numPoints; i++) {
          const angle = (i / numPoints) * Math.PI * 2
          points.push({
            x: centerX + Math.cos(angle) * radius + (Math.random() - 0.5) * 150,
            y: centerY + Math.sin(angle) * radius + (Math.random() - 0.5) * 150,
            vx: (Math.random() - 0.5) * speed,
            vy: (Math.random() - 0.5) * speed,
            connections: []
          })
        }
      }

      const animate = () => {
        if (!ctx || !canvas.value) return

        ctx.clearRect(0, 0, canvas.value.width, canvas.value.height)

        // Atualizar posições dos pontos
        points.forEach(point => {
          point.x += point.vx
          point.y += point.vy

          // Rebater nas bordas
          if (point.x < 0 || point.x > canvas.value!.width) point.vx *= -1
          if (point.y < 0 || point.y > canvas.value!.height) point.vy *= -1
        })

        // Encontrar conexões
        points.forEach((point, i) => {
          point.connections = []
          points.forEach((otherPoint, j) => {
            if (i !== j) {
              const distance = Math.sqrt(
                Math.pow(point.x - otherPoint.x, 2) + Math.pow(point.y - otherPoint.y, 2)
              )
              if (distance < maxDistance) {
                point.connections.push(j)
              }
            }
          })
        })

        // Desenhar conexões com glow
        points.forEach((point, i) => {
          point.connections.forEach(connectionIndex => {
            const otherPoint = points[connectionIndex]
            const distance = Math.sqrt(
              Math.pow(point.x - otherPoint.x, 2) + Math.pow(point.y - otherPoint.y, 2)
            )
            const opacity = 1 - distance / maxDistance

            // Glow effect
            ctx!.shadowColor = '#00BCD4'
            ctx!.shadowBlur = 10
            ctx!.strokeStyle = `rgba(0, 188, 212, ${opacity * 0.6})`
            ctx!.lineWidth = 1
            ctx!.beginPath()
            ctx!.moveTo(point.x, point.y)
            ctx!.lineTo(otherPoint.x, otherPoint.y)
            ctx!.stroke()
          })
        })

        // Desenhar pontos com glow
        points.forEach(point => {
          ctx!.shadowColor = '#00BCD4'
          ctx!.shadowBlur = 15
          ctx!.fillStyle = '#00BCD4'
          ctx!.beginPath()
          ctx!.arc(point.x, point.y, 3, 0, Math.PI * 2)
          ctx!.fill()

          // Inner glow
          ctx!.shadowBlur = 5
          ctx!.fillStyle = '#FFFFFF'
          ctx!.beginPath()
          ctx!.arc(point.x, point.y, 1.5, 0, Math.PI * 2)
          ctx!.fill()
        })

        ctx!.shadowBlur = 0

        animationId = requestAnimationFrame(animate)
      }

      onMounted(() => {
        setTimeout(initCanvas, 100)
      })

      onUnmounted(() => {
        if (animationId) {
          cancelAnimationFrame(animationId)
        }
      })

      return { canvas }
    }
  })
</script>

<style scoped>
  .animated-bg {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 1;
  }

  .network-canvas {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
  }
</style>
